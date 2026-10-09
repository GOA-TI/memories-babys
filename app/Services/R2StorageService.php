<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class R2StorageService
{
    public function isConfigured(): bool
    {
        return filled(config('filesystems.disks.r2.key'))
            && filled(config('filesystems.disks.r2.secret'))
            && filled(config('filesystems.disks.r2.bucket'))
            && filled(config('filesystems.disks.r2.endpoint'));
    }

    public function hasPublicUrl(): bool
    {
        return filled(config('filesystems.disks.r2.url'));
    }

    /**
     * @return array{disk: string, key: string, public_url: ?string}
     */
    public function upload(UploadedFile $file, string $targetName, ?string $albumSlug = null): array
    {
        return $this->uploadContents(
            (string) file_get_contents($file->getRealPath()),
            $targetName,
            $file->getMimeType() ?: 'application/octet-stream',
            $albumSlug,
        );
    }

    /**
     * @return array{disk: string, key: string, public_url: ?string}
     */
    public function uploadContents(string $contents, string $targetName, string $contentType, ?string $albumSlug = null): array
    {
        $folder = $albumSlug ? 'carpetas/'.Str::slug($albumSlug) : 'sueltas';
        $key = 'memorias/'.$folder.'/'.now()->format('Y/m').'/'.$targetName;

        Storage::disk('r2')->put($key, $contents, [
            'visibility' => 'public',
            'ContentType' => $contentType ?: 'application/octet-stream',
        ]);

        $publicUrl = null;
        if ($this->hasPublicUrl()) {
            $publicUrl = Storage::disk('r2')->url($key);
        }

        return [
            'disk' => 'r2',
            'key' => $key,
            'public_url' => $publicUrl,
        ];
    }

    public function testConnection(): void
    {
        $probe = 'memorias/_probe/'.now()->format('YmdHis').'.txt';
        Storage::disk('r2')->put($probe, 'ok', ['visibility' => 'public']);
        Storage::disk('r2')->delete($probe);
    }
}
