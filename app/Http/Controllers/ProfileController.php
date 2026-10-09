<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Services\MemberAvatarService;
use App\Support\AvatarPhotoRules;
use App\Support\FamilyRelationship;
use App\Support\FamilySession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function show(FamilyMember $member): View
    {
        abort_unless($member->is_active, 404);

        $viewer = FamilySession::member();
        $isSelf = $viewer && (int) $viewer->id === (int) $member->id;

        $recentPosts = $member->memoryPosts()
            ->where('show_on_wall', true)
            ->with(['mediaItems'])
            ->latest()
            ->limit(12)
            ->get();

        return view('profile.show', compact('member', 'isSelf', 'recentPosts'));
    }

    public function edit(): View
    {
        $member = FamilySession::member();
        $relationships = FamilyRelationship::options();

        return view('profile.edit', compact('member', 'relationships'));
    }

    public function update(Request $request, MemberAvatarService $avatars): RedirectResponse
    {
        $member = FamilySession::member();
        $keys = array_keys(FamilyRelationship::options());

        if (! $request->filled('relationship') && $member->hasRelationship()) {
            $request->merge(['relationship' => $member->relationship]);
        }

        $data = $request->validate([
            'relationship' => ['required', 'string', Rule::in($keys)],
            'avatar_emoji' => ['nullable', 'string', 'max:16'],
            'avatar_photo' => AvatarPhotoRules::validation(),
            'remove_avatar_photo' => ['sometimes', 'boolean'],
        ], [
            'relationship.required' => 'Elige tu parentesco antes de guardar.',
            'relationship.in' => 'El parentesco seleccionado no es válido.',
            'avatar_photo.max' => 'La foto es muy pesada (máximo 8 MB).',
            'avatar_photo.mimes' => 'Usa JPG, PNG o HEIC para la foto.',
        ]);

        $member->update([
            'relationship' => $data['relationship'],
            'avatar_emoji' => $data['avatar_emoji'] ?? $member->avatar_emoji ?? '🙂',
        ]);

        try {
            if ($request->boolean('remove_avatar_photo')) {
                $avatars->remove($member);
            } elseif ($request->hasFile('avatar_photo')) {
                $avatars->store($member, $request->file('avatar_photo'));
            }
        } catch (RuntimeException $e) {
            return back()
                ->withInput()
                ->withErrors(['avatar_photo' => $e->getMessage()]);
        }

        $member->refresh();
        FamilySession::syncTopbarAvatar($member);

        return redirect()
            ->route('profile.edit')
            ->with('success', '¡Listo! Tu perfil quedó actualizado.');
    }

    public function avatar(FamilyMember $member): StreamedResponse
    {
        abort_unless(FamilySession::member(), 403);
        abort_unless($member->hasAvatarPhoto(), 404);

        $cacheHeaders = [
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ];

        if ($member->avatar_disk === 'r2') {
            abort_unless(Storage::disk('r2')->exists($member->avatar_path), 404);
            $mime = $this->avatarMimeType($member->avatar_path);

            return Storage::disk('r2')->response($member->avatar_path, 'avatar.jpg', array_merge($cacheHeaders, [
                'Content-Type' => $mime,
            ]));
        }

        abort_unless($member->avatar_path && Storage::disk('local')->exists($member->avatar_path), 404);

        $mime = $this->avatarMimeType($member->avatar_path);

        return Storage::disk('local')->response($member->avatar_path, 'avatar.jpg', array_merge($cacheHeaders, [
            'Content-Type' => $mime,
        ]));
    }

    private function avatarMimeType(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($ext) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'heic' => 'image/heic',
            'heif' => 'image/heif',
            default => 'image/jpeg',
        };
    }
}
