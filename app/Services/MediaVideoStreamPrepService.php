<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class MediaVideoStreamPrepService
{
    /** Reempaqueta MP4/MOV con moov al inicio (arranque más rápido en el móvil). */
    public function fastStartIfNeeded(UploadedFile $file): UploadedFile
    {
        if (! $this->isSupportedVideo($file) || ! $this->ffmpegAvailable()) {
            return $file;
        }

        $input = $file->getRealPath();
        if (! $input || ! is_readable($input)) {
            return $file;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'mp4');
        $tmp = tempnam(sys_get_temp_dir(), 'samuel-faststart-');
        if ($tmp === false) {
            return $file;
        }

        $output = $tmp.'.'.$ext;
        @unlink($tmp);

        $bin = (string) config('samuel.ffmpeg_bin', 'ffmpeg');
        $cmd = sprintf(
            '%s -hide_banner -loglevel error -y -i %s -c copy -movflags +faststart %s 2>&1',
            escapeshellarg($bin),
            escapeshellarg($input),
            escapeshellarg($output)
        );

        exec($cmd, $out, $code);
        if ($code !== 0 || ! is_file($output) || filesize($output) < 1024) {
            @unlink($output);
            Log::warning('Samuel: faststart video omitido', ['output' => implode("\n", $out)]);

            return $file;
        }

        return new UploadedFile(
            $output,
            $file->getClientOriginalName(),
            $file->getMimeType() ?: 'video/mp4',
            null,
            true
        );
    }

    private function isSupportedVideo(UploadedFile $file): bool
    {
        $mime = strtolower((string) $file->getMimeType());
        if (str_starts_with($mime, 'video/')) {
            return true;
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        return in_array($ext, ['mp4', 'mov', 'm4v', '3gp', '3gpp'], true);
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
}
