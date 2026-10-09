<?php

namespace App\Support;

/**
 * HEIC/HEIF (iPhone, Live Photo) → JPEG vía ImageMagick o ffmpeg.
 */
final class HeicImageConverter
{
    public static function looksLikeHeic(string $binary): bool
    {
        if (strlen($binary) < 12) {
            return false;
        }

        if (substr($binary, 4, 4) !== 'ftyp') {
            return false;
        }

        $brand = substr($binary, 8, 4);

        return in_array($brand, ['heic', 'heix', 'hevc', 'hevx', 'mif1', 'msf1', 'avif'], true);
    }

    public static function convertBinaryToJpeg(string $binary): ?string
    {
        if ($binary === '') {
            return null;
        }

        $in = tempnam(sys_get_temp_dir(), 'samuel-heic-in-');
        $out = tempnam(sys_get_temp_dir(), 'samuel-heic-out-');
        if ($in === false || $out === false) {
            return null;
        }

        $inPath = $in.'.heic';
        $outPath = $out.'.jpg';
        @unlink($in);
        @unlink($out);
        file_put_contents($inPath, $binary);

        $jpeg = self::convertPathToJpeg($inPath, $outPath);
        @unlink($inPath);
        @unlink($outPath);

        return $jpeg;
    }

    public static function convertPathToJpeg(string $sourcePath, ?string $destPath = null): ?string
    {
        if (! is_file($sourcePath)) {
            return null;
        }

        $outPath = $destPath;
        $cleanupOut = false;
        if ($outPath === null) {
            $tmp = tempnam(sys_get_temp_dir(), 'samuel-heic-out-');
            if ($tmp === false) {
                return null;
            }
            $outPath = $tmp.'.jpg';
            @unlink($tmp);
            $cleanupOut = true;
        }

        $convert = trim((string) shell_exec('command -v convert'));
        if ($convert !== '') {
            $cmd = escapeshellcmd($convert).' '.escapeshellarg($sourcePath).' '.escapeshellarg($outPath).' 2>&1';
            exec($cmd, $output, $code);
            if ($code === 0 && is_file($outPath) && filesize($outPath) > 128) {
                $jpeg = (string) file_get_contents($outPath);
                if ($cleanupOut) {
                    @unlink($outPath);
                }

                return $jpeg !== '' ? $jpeg : null;
            }
        }

        $ffmpeg = trim((string) config('samuel.ffmpeg_bin', 'ffmpeg'));
        if ($ffmpeg !== '' && trim((string) shell_exec('command -v '.escapeshellarg($ffmpeg))) !== '') {
            $cmd = sprintf(
                '%s -hide_banner -loglevel error -i %s -frames:v 1 -q:v 2 -y %s 2>&1',
                escapeshellarg($ffmpeg),
                escapeshellarg($sourcePath),
                escapeshellarg($outPath)
            );
            exec($cmd, $output, $code);
            if ($code === 0 && is_file($outPath) && filesize($outPath) > 128) {
                $jpeg = (string) file_get_contents($outPath);
                if ($cleanupOut) {
                    @unlink($outPath);
                }

                return $jpeg !== '' ? $jpeg : null;
            }
        }

        if ($cleanupOut) {
            @unlink($outPath);
        }

        return null;
    }
}
