<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\DriveAccount;
use App\Models\SamuelProfile;
use App\Services\AvatarImageProcessor;
use App\Services\FamilyNotificationService;
use App\Services\MediaUploadService;
use App\Services\MediaVideoPosterService;
use App\Support\MemoryMomentInput;
use App\Support\UploadMediaValidator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class UploadController extends Controller
{
    public function create(): View
    {
        $albums = Album::query()->where('is_visible', true)->orderBy('sort_order')->get();
        $driveAccounts = DriveAccount::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('upload', compact('albums', 'driveAccounts'));
    }

    public function preview(Request $request): Response
    {
        $file = $request->file('file');
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            abort(422, 'Archivo no válido');
        }

        if ($file->getSize() > UploadMediaValidator::maxKilobytes() * 1024) {
            abort(422, 'Archivo demasiado grande');
        }

        if (! UploadMediaValidator::isAllowedMedia($file)) {
            abort(422, 'Formato no permitido');
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $isVideo = str_starts_with($mime, 'video/')
            || in_array($ext, ['mp4', 'mov', 'webm', 'm4v', '3gp', '3gpp'], true);

        if ($isVideo) {
            $jpeg = app(MediaVideoPosterService::class)->jpegPreviewFromUpload($file);
        } else {
            try {
                $jpeg = app(AvatarImageProcessor::class)->toJpegBytes($file, 1280, 88);
            } catch (\RuntimeException $e) {
                abort(422, $e->getMessage());
            }
        }

        if ($jpeg === null || $jpeg === '') {
            abort(422, 'No pudimos generar la vista previa.');
        }

        return response($jpeg, 200, [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function store(Request $request, MediaUploadService $uploader, FamilyNotificationService $notifier)
    {
        if (! $request->hasFile('media') && (int) $request->server('CONTENT_LENGTH') > 0) {
            $maxMb = (int) config('samuel.upload.max_mb', 300);
            $maxMin = (int) config('samuel.upload.max_video_minutes', 3);

            return back()
                ->withErrors([
                    'media' => 'No llegó el video completo al servidor. Máximo '.$maxMb.' MB por archivo (~'.$maxMin.' min). Prueba calidad media o un clip más corto.',
                ])
                ->withInput();
        }

        $validator = Validator::make($request->all(), array_merge([
            'caption' => ['nullable', 'string', 'max:500'],
            'album_id' => ['nullable', 'exists:albums,id'],
            'show_on_wall' => ['nullable', 'boolean'],
            'drive_account_id' => ['nullable', 'exists:drive_accounts,id'],
        ], MemoryMomentInput::validationRules()));

        UploadMediaValidator::attachMultipleMediaRules($validator, 'media', true);

        $data = $validator->validate();

        $memoryAt = MemoryMomentInput::fromRequest($request);
        $birth = SamuelProfile::current()->birthAtLocal();
        if ($birth && $memoryAt->lt($birth)) {
            return back()->withErrors(['memory_date' => 'Esa fecha es anterior al nacimiento de '.SamuelProfile::current()->displayName().'.'])->withInput();
        }

        $member = \App\Support\FamilySession::member();
        $album = isset($data['album_id']) ? Album::query()->find($data['album_id']) : null;
        $drive = isset($data['drive_account_id']) ? DriveAccount::query()->find($data['drive_account_id']) : null;

        $files = $request->file('media');
        $files = is_array($files) ? array_values($files) : [$files];

        try {
            $post = $uploader->storePost(
                $member,
                $files,
                $album,
                $data['caption'] ?? null,
                (bool) ($data['show_on_wall'] ?? true),
                $drive,
                $memoryAt,
            );
        } catch (\RuntimeException $e) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['media' => $e->getMessage()])->withInput();
        }

        try {
            $notifier->notifyNewPost($post, $member);
        } catch (\Throwable) {
            // La publicación ya está guardada; no bloquear por avisos.
        }

        $redirect = redirect()
            ->route('memories.index')
            ->with('success', '¡Gracias! Tu publicación ya está guardada.')
            ->with('highlight_id', $post->id);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'redirect' => $redirect->getTargetUrl(),
                'message' => '¡Gracias! Tu publicación ya está guardada.',
                'highlight_id' => $post->id,
            ]);
        }

        return $redirect;
    }
}
