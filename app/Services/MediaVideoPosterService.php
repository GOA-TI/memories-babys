<?php

namespace App\Services;

use App\Models\MemoryMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MediaVideoPosterService
{
    private const JPEG_QUALITY = 78;

    private const MAX_WIDTH = 720;

    public function __construct(
        private readonly R2StorageService $r2Storage,
    ) {}

    public function canServePoster(MemoryMedia $media): bool
    {
        return $media->isVideo()
            && (filled($media->poster_path) || $this->canGenerate($media));
    }

    public function canGenerate(MemoryMedia $media): bool
    {
        return $media->isVideo()
            && $media->canStreamViaApp()
            && $this->ffmpegAvailable();
    }

    public function jpegPreviewFromUpload(UploadedFile $file): ?string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return null;
        }

        return $this->extractJpegFromPath($path);
    }

    public function generateFromUpload(UploadedFile $file, MemoryMedia $media): void
    {
        if (! $media->isVideo() || ! $this->ffmpegAvailable()) {
            return;
        }

        $jpeg = $this->extractJpegFromPath($file->getRealPath());
        if ($jpeg === null) {
            return;
        }

        $this->persistPoster($media, $jpeg);
    }

    public function ensurePoster(MemoryMedia $media): bool
    {
        if (filled($media->poster_path)) {
            return true;
        }

        if (! $this->canGenerate($media)) {
            return false;
        }

        $sourcePath = $this->materializeOriginal($media);
        if ($sourcePath === null) {
            return false;
        }

        try {
            $jpeg = $this->extractJpegFromPath($sourcePath);
            if ($jpeg === null) {
                return false;
            }

            $this->persistPoster($media, $jpeg);

            return filled($media->fresh()->poster_path);
        } finally {
            if (isset($sourcePath) && str_starts_with($sourcePath, sys_get_temp_dir())) {
                @unlink($sourcePath);
            }
        }
    }

    public function respond(MemoryMedia $media): Response
    {
        if (filled($media->poster_path)) {
            $disk = $media->storage_disk === 'r2' ? 'r2' : 'local';
            if (Storage::disk($disk)->exists($media->poster_path)) {
                return Storage::disk($disk)->response($media->poster_path, null, $this->headers());
            }
        }

        if ($this->ensurePoster($media)) {
            $media->refresh();
            $disk = $media->storage_disk === 'r2' ? 'r2' : 'local';
            if (filled($media->poster_path) && Storage::disk($disk)->exists($media->poster_path)) {
                return Storage::disk($disk)->response($media->poster_path, null, $this->headers());
            }
        }

        $cachePath = $this->cachePath($media);
        if (is_file($cachePath)) {
            return response()->file($cachePath, $this->headers());
        }

        abort(404);
    }

    private function persistPoster(MemoryMedia $media, string $jpeg): void
    {
        $jpeg = $this->resizeJpeg($jpeg) ?? $jpeg;
        $posterKey = $this->posterKeyFor($media);

        if ($media->storage_disk === 'r2') {
            Storage::disk('r2')->put($posterKey, $jpeg, [
                'visibility' => 'public',
                'ContentType' => 'image/jpeg',
            ]);
            $publicUrl = $this->r2Storage->hasPublicUrl()
                ? Storage::disk('r2')->url($posterKey)
                : null;
            $media->update([
                'poster_path' => $posterKey,
                'poster_public_url' => $publicUrl,
            ]);

            return;
        }

        Storage::disk('local')->put($posterKey, $jpeg);
        $media->update(['poster_path' => $posterKey, 'poster_public_url' => null]);
    }

    private function posterKeyFor(MemoryMedia $media): string
    {
        $base = pathinfo((string) $media->local_path, PATHINFO_FILENAME) ?: 'video-'.$media->id;

        return 'memorias/posters/'.$media->id.'-'.$base.'.jpg';
    }

    private function materializeOriginal(MemoryMedia $media): ?string
    {
        $disk = $media->storage_disk === 'r2' ? 'r2' : 'local';
        if (! filled($media->local_path) || ! Storage::disk($disk)->exists($media->local_path)) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'samuel-vid-');
        if ($tmp === false) {
            return null;
        }

        $target = $tmp.'.'.pathinfo($media->original_name ?: 'video.mp4', PATHINFO_EXTENSION);
        rename($tmp, $target);
        file_put_contents($target, Storage::disk($disk)->get($media->local_path));

        return $target;
    }

    private function extractJpegFromPath(string $path): ?string
    {
        if (! is_file($path) || ! $this->ffmpegAvailable()) {
            return null;
        }

        $out = tempnam(sys_get_temp_dir(), 'samuel-poster-');
        if ($out === false) {
            return null;
        }
        $jpg = $out.'.jpg';
        @unlink($out);

        $bin = (string) config('samuel.ffmpeg_bin', 'ffmpeg');
        $cmd = sprintf(
            '%s -hide_banner -loglevel error -ss 0.35 -i %s -frames:v 1 -q:v %d -y %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($path),
            4,
            escapeshellarg($jpg)
        );

        exec($cmd, $output, $code);
        if ($code !== 0 || ! is_file($jpg)) {
            Log::warning('Samuel: no se pudo generar poster de video', ['output' => implode("\n", $output)]);

            return null;
        }

        $bytes = file_get_contents($jpg);
        @unlink($jpg);

        return is_string($bytes) && $bytes !== '' ? $bytes : null;
    }

    private function resizeJpeg(string $jpeg): ?string
    {
        if (! function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($jpeg);
        if ($source === false) {
            return null;
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
        $out = ob_get_clean();

        return is_string($out) && $out !== '' ? $out : null;
    }

    private function cachePath(MemoryMedia $media): string
    {
        $stamp = $media->updated_at?->timestamp ?? 0;

        return storage_path('framework/cache/video-posters/'.$media->id.'-'.$stamp.'.jpg');
    }

    private function ffmpegAvailable(): bool
    {
        static $ok = null;
        if ($ok !== null) {
            return $ok;
        }
        $bin = (string) config('samuel.ffmpeg_bin', 'ffmpeg');
        $out = [];
        exec(sprintf('%s -version 2>&1', escapeshellarg($bin)), $out, $code);
        $ok = $code === 0;

        return $ok;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'public, max-age=604800, stale-while-revalidate=86400',
        ];
    }
}
