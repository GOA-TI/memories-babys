<?php

namespace App\Services;

use App\Support\HeicImageConverter;
use App\Support\PreparedUploadBinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * iPhone suele enviar HEIC/HEIF (Live Photo). El muro y el navegador necesitan JPEG;
 * guardamos alta calidad (hasta 4096 px) sin tocar JPG/PNG originales.
 */
class MemoryImageUploadNormalizer
{
    private const MAX_SIDE = 4096;

    public function __construct(
        private readonly AvatarImageProcessor $images,
    ) {}

    public function needsHeicConversion(UploadedFile $file): bool
    {
        return $this->isRasterImage($file) && $this->isHeic($file);
    }

    public function prepare(UploadedFile $file): PreparedUploadBinary
    {
        if (! $this->needsHeicConversion($file)) {
            throw new \InvalidArgumentException('prepare() solo aplica a fotos HEIC/HEIF.');
        }

        $jpeg = $this->images->toJpegBytes($file, self::MAX_SIDE, 90);
        $name = $this->jpegFilename($file->getClientOriginalName() ?: 'recuerdo.heic');

        return new PreparedUploadBinary($jpeg, 'image/jpeg', $name, true);
    }

    public function isRasterImage(UploadedFile $file): bool
    {
        if (str_starts_with(strtolower((string) $file->getMimeType()), 'video/')) {
            return false;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        $imageExt = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'avif', 'dng'];

        if (in_array($ext, $imageExt, true)) {
            return true;
        }

        $mime = strtolower((string) $file->getMimeType());

        return str_starts_with($mime, 'image/')
            || ($mime === 'application/octet-stream' && in_array($ext, ['heic', 'heif'], true));
    }

    private function isHeic(UploadedFile $file): bool
    {
        $mime = strtolower($file->getMimeType() ?: '');
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if (in_array($ext, ['heic', 'heif'], true)
            || str_contains($mime, 'heic')
            || str_contains($mime, 'heif')) {
            return true;
        }

        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            return false;
        }

        $head = @file_get_contents($path, false, null, 0, 32);

        return is_string($head) && HeicImageConverter::looksLikeHeic($head);
    }

    private function jpegFilename(string $original): string
    {
        $base = pathinfo($original, PATHINFO_FILENAME) ?: 'recuerdo';
        $base = Str::slug($base) ?: 'recuerdo';

        return $base.'.jpg';
    }
}
