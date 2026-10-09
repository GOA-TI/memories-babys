<?php

namespace App\Services;

use App\Models\MemoryMedia;
use App\Support\HeicImageConverter;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaFeedImageService
{
    private const MAX_WIDTH = 1280;

    private const JPEG_QUALITY = 85;

    public function canOptimize(MemoryMedia $media): bool
    {
        return $media->isImage()
            && in_array($media->storage_disk, ['local', 'r2'], true)
            && filled($media->local_path);
    }

    public function respond(MemoryMedia $media): Response
    {
        if (! $this->canOptimize($media)) {
            abort(404);
        }

        $cachePath = $this->cachePath($media);
        if (is_file($cachePath)) {
            return $this->fileResponse($cachePath);
        }

        $binary = $this->readOriginal($media);
        if ($binary === null) {
            abort(404);
        }

        $jpeg = $this->toFeedJpeg($binary, $media);
        if ($jpeg === null) {
            abort(404);
        }

        if (! is_dir(dirname($cachePath))) {
            mkdir(dirname($cachePath), 0775, true);
        }
        file_put_contents($cachePath, $jpeg);

        return response($jpeg, 200, $this->headers('image/jpeg'));
    }

    private function cachePath(MemoryMedia $media): string
    {
        $stamp = $media->updated_at?->timestamp ?? 0;

        return storage_path('framework/cache/feed-images/'.$media->id.'-'.$stamp.'.jpg');
    }

    private function readOriginal(MemoryMedia $media): ?string
    {
        $disk = $media->storage_disk === 'r2' ? 'r2' : 'local';
        if (! Storage::disk($disk)->exists($media->local_path)) {
            return null;
        }

        return Storage::disk($disk)->get($media->local_path);
    }

    private function isHeicPayload(string $binary, MemoryMedia $media): bool
    {
        $path = strtolower((string) $media->local_path);
        if (str_ends_with($path, '.heic') || str_ends_with($path, '.heif')) {
            return true;
        }

        $mime = strtolower((string) $media->mime_type);
        if (str_contains($mime, 'heic') || str_contains($mime, 'heif')) {
            return true;
        }

        return HeicImageConverter::looksLikeHeic($binary);
    }

    private function toFeedJpeg(string $binary, MemoryMedia $media): ?string
    {
        if ($this->isHeicPayload($binary, $media)) {
            $converted = HeicImageConverter::convertBinaryToJpeg($binary);
            if ($converted === null) {
                return null;
            }
            $binary = $converted;
        }

        if (! function_exists('imagecreatefromstring')) {
            return $binary;
        }

        $source = @imagecreatefromstring($binary);
        if ($source === false) {
            return str_starts_with($binary, "\xFF\xD8") ? $binary : null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        if ($width > self::MAX_WIDTH) {
            $newHeight = (int) round($height * (self::MAX_WIDTH / $width));
            $resized = imagescale($source, self::MAX_WIDTH, $newHeight);
            imagedestroy($source);
            if ($resized === false) {
                return null;
            }
            $source = $resized;
        }

        ob_start();
        imagejpeg($source, null, self::JPEG_QUALITY);
        imagedestroy($source);
        $jpeg = ob_get_clean();

        return is_string($jpeg) && $jpeg !== '' ? $jpeg : null;
    }

    private function fileResponse(string $path): Response
    {
        return response()->file($path, $this->headers('image/jpeg'));
    }

    /** @return array<string, string> */
    private function headers(string $contentType): array
    {
        return [
            'Content-Type' => $contentType,
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
        ];
    }
}
