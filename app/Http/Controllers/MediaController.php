<?php

namespace App\Http\Controllers;

use App\Models\MemoryMedia;
use App\Models\MemoryPost;
use App\Services\MediaFeedImageService;
use App\Services\MediaVideoPosterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaController extends Controller
{
    public function __construct(
        private readonly MediaFeedImageService $feedImages,
        private readonly MediaVideoPosterService $videoPosters,
    ) {}
    public function toggleFavorite(Request $request, MemoryPost $post)
    {
        $post->update(['is_favorite' => ! $post->is_favorite]);

        if ($request->expectsJson()) {
            return response()->json([
                'is_favorite' => (bool) $post->is_favorite,
            ]);
        }

        return back()->with('success', $post->is_favorite ? 'Añadido a favoritos.' : 'Quitado de favoritos.');
    }

    public function serve(Request $request, MemoryMedia $media): Response|StreamedResponse
    {
        if ($request->boolean('feed') && $this->feedImages->canOptimize($media)) {
            return $this->feedImages->respond($media);
        }

        if ($request->boolean('poster') && $media->isVideo()) {
            return $this->videoPosters->respond($media);
        }

        if (
            ! $request->boolean('feed')
            && ! $request->boolean('poster')
            && $media->storage_disk === 'r2'
            && filled($media->public_url)
        ) {
            return redirect()->away($media->public_url);
        }

        $cacheHeaders = [
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
            'Accept-Ranges' => 'bytes',
        ];

        if ($media->storage_disk === 'r2' && $media->local_path && Storage::disk('r2')->exists($media->local_path)) {
            return Storage::disk('r2')->response($media->local_path, $media->original_name, array_merge([
                'Content-Type' => $media->mime_type,
            ], $cacheHeaders));
        }

        abort_unless($media->local_path && Storage::disk('local')->exists($media->local_path), 404);

        return Storage::disk('local')->response($media->local_path, $media->original_name, array_merge([
            'Content-Type' => $media->mime_type,
        ], $cacheHeaders));
    }

    public function download(MemoryMedia $media): Response|StreamedResponse
    {
        abort_unless($media->canStreamViaApp(), 404);

        $filename = $media->original_name ?: ($media->isVideo() ? 'video.mp4' : 'recuerdo.jpg');
        $headers = [
            'Content-Type' => $media->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        if ($media->storage_disk === 'r2' && $media->local_path && Storage::disk('r2')->exists($media->local_path)) {
            return Storage::disk('r2')->response($media->local_path, $filename, $headers);
        }

        abort_unless($media->local_path && Storage::disk('local')->exists($media->local_path), 404);

        return Storage::disk('local')->response($media->local_path, $filename, $headers);
    }
}
