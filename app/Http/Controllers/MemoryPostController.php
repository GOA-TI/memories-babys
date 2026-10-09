<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\MemoryPost;
use App\Models\SamuelProfile;
use App\Services\MediaDeleteService;
use App\Services\MediaUploadService;
use App\Support\FamilySession;
use App\Support\MemoryMomentInput;
use App\Support\MemoryPostAuthorizer;
use App\Support\UploadMediaValidator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MemoryPostController extends Controller
{
    public function edit(MemoryPost $post): View|RedirectResponse
    {
        if (! MemoryPostAuthorizer::canManage($post, FamilySession::member())) {
            return redirect()->route('wall')->with('error', MemoryPostAuthorizer::denyMessage());
        }

        $post->load('mediaItems');
        $albums = Album::query()->where('is_visible', true)->orderBy('sort_order')->get();

        return view('posts.edit', compact('post', 'albums'));
    }

    public function update(Request $request, MemoryPost $post, MediaUploadService $uploader): RedirectResponse
    {
        if (! MemoryPostAuthorizer::canManage($post, FamilySession::member())) {
            return back()->with('error', MemoryPostAuthorizer::denyMessage());
        }

        $validator = Validator::make($request->all(), array_merge([
            'caption' => ['nullable', 'string', 'max:500'],
            'album_id' => ['nullable', 'exists:albums,id'],
            'show_on_wall' => ['nullable', 'boolean'],
            'redirect' => ['nullable', 'string'],
        ], MemoryMomentInput::validationRules()));

        UploadMediaValidator::attachMultipleMediaRules($validator, 'media', false);

        $data = $validator->validate();

        $album = isset($data['album_id']) ? Album::query()->find($data['album_id']) : null;
        $showOnWall = (bool) ($data['show_on_wall'] ?? false);

        $memoryAt = $request->filled('memory_date')
            ? MemoryMomentInput::fromRequest($request)
            : null;

        if ($memoryAt) {
            $birth = SamuelProfile::current()->birthAtLocal();
            if ($birth && $memoryAt->lt($birth)) {
                return back()->withErrors(['memory_date' => 'Esa fecha es anterior al nacimiento.'])->withInput();
            }
        }

        $uploader->updatePostMetadata(
            $post,
            $data['caption'] ?? null,
            $album,
            $showOnWall,
            $memoryAt,
        );

        if ($request->hasFile('media')) {
            $files = $request->file('media');
            $files = is_array($files) ? $files : [$files];
            $uploader->appendFilesToPost($post, $files, $album);
        }

        return $this->redirectAfterManage($request, 'Publicación actualizada.', $post->id);
    }

    public function destroy(Request $request, MemoryPost $post, MediaDeleteService $deleter): RedirectResponse
    {
        if (! MemoryPostAuthorizer::canDelete($post, FamilySession::member())) {
            return back()->with('error', MemoryPostAuthorizer::denyDeleteMessage());
        }

        $deleter->deletePost($post);

        return $this->redirectAfterManage($request, 'Publicación eliminada.');
    }

    private function redirectAfterManage(Request $request, string $message, ?int $highlightId = null): RedirectResponse
    {
        $toMemories = $request->input('redirect') === 'memories'
            || $request->query('from') === 'memories';

        $redirect = redirect()->route($toMemories ? 'memories.index' : 'wall')
            ->with('success', $message);

        if ($highlightId) {
            $redirect->with('highlight_id', $highlightId);
        }

        return $redirect;
    }
}
