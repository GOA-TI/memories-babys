<?php

namespace App\Services;

use App\Support\HeicImageConverter;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class AvatarImageProcessor
{
    public function toJpegBytes(UploadedFile $file, int $maxSide = 960, int $jpegQuality = 86): string
    {
        $path = $file->getRealPath();
        if (! $path || ! is_readable($path)) {
            throw new RuntimeException('No se pudo leer la foto.');
        }

        $mime = strtolower($file->getMimeType() ?: '');
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        if ($this->isHeic($mime, $ext)) {
            $jpegPath = $this->convertHeicToJpegPath($path);
            try {
                $bytes = $this->jpegBytesFromPath($jpegPath, $maxSide, $jpegQuality);
            } finally {
                @unlink($jpegPath);
            }

            return $bytes;
        }

        $image = $this->openRaster($path, $mime, $ext);
        if (! $image) {
            throw new RuntimeException('Formato de imagen no soportado. Usa JPG o PNG.');
        }

        try {
            $image = $this->fitWithin($image, $maxSide);
            ob_start();
            imagejpeg($image, null, max(60, min(100, $jpegQuality)));
            $bytes = ob_get_clean();
            if ($bytes === false || $bytes === '') {
                throw new RuntimeException('No se pudo generar la foto.');
            }

            return $bytes;
        } finally {
            imagedestroy($image);
        }
    }

    private function isHeic(string $mime, string $ext): bool
    {
        return in_array($ext, ['heic', 'heif'], true)
            || str_contains($mime, 'heic')
            || str_contains($mime, 'heif');
    }

    private function convertHeicToJpegPath(string $sourcePath): string
    {
        $out = tempnam(sys_get_temp_dir(), 'samuel_av_');
        if ($out === false) {
            throw new RuntimeException('No se pudo preparar la conversión.');
        }
        $jpgPath = $out.'.jpg';
        @unlink($out);

        $jpeg = HeicImageConverter::convertPathToJpeg($sourcePath, $jpgPath);
        if ($jpeg === null || ! is_file($jpgPath) || filesize($jpgPath) < 128) {
            throw new RuntimeException('No pudimos abrir la foto HEIC. Prueba otra imagen o usa JPG.');
        }

        return $jpgPath;
    }

    /**
     * @return \GdImage|false
     */
    private function openRaster(string $path, string $mime, string $ext)
    {
        if (str_contains($mime, 'jpeg') || str_contains($mime, 'jpg') || $ext === 'jpg' || $ext === 'jpeg') {
            return @imagecreatefromjpeg($path);
        }
        if (str_contains($mime, 'png') || $ext === 'png') {
            return @imagecreatefrompng($path);
        }
        if ((str_contains($mime, 'webp') || $ext === 'webp') && function_exists('imagecreatefromwebp')) {
            return @imagecreatefromwebp($path);
        }
        if (str_contains($mime, 'gif') || $ext === 'gif') {
            return @imagecreatefromgif($path);
        }

        return @imagecreatefromstring((string) file_get_contents($path));
    }

    /**
     * @param \GdImage $image
     * @return \GdImage
     */
    private function fitWithin($image, int $maxSide)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= 0 || $height <= 0) {
            return $image;
        }

        $longest = max($width, $height);
        if ($longest <= $maxSide) {
            return $image;
        }

        $scale = $maxSide / $longest;
        $newW = (int) max(1, round($width * $scale));
        $newH = (int) max(1, round($height * $scale));

        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    private function jpegBytesFromPath(string $path, int $maxSide, int $jpegQuality = 86): string
    {
        $image = @imagecreatefromjpeg($path);
        if (! $image) {
            throw new RuntimeException('No se pudo procesar la foto.');
        }

        try {
            $image = $this->fitWithin($image, $maxSide);
            ob_start();
            imagejpeg($image, null, max(60, min(100, $jpegQuality)));
            $bytes = ob_get_clean();
            if ($bytes === false || $bytes === '') {
                throw new RuntimeException('No se pudo generar la foto.');
            }

            return $bytes;
        } finally {
            imagedestroy($image);
        }
    }
}
