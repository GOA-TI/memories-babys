<?php

namespace App\Services;

use App\Models\MemoryMedia;
use App\Models\MemoryPost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaDeleteService
{
    public function __construct(
        private readonly GoogleDriveStorage $driveStorage,
    ) {}

    public function deleteMemory(MemoryMedia $media): void
    {
        $this->deleteStoredFile($media);
        $media->delete();
    }

    public function deletePost(MemoryPost $post): void
    {
        $post->load(['mediaItems.driveAccount']);

        foreach ($post->mediaItems as $media) {
            $this->deleteStoredFile($media);
        }

        $post->delete();
    }

    public function deleteStoredFile(MemoryMedia $media): void
    {
        if ($media->storage_disk === 'r2' && $media->local_path) {
            try {
                Storage::disk('r2')->delete($media->local_path);
            } catch (\Throwable $e) {
                Log::warning('R2: no se pudo borrar archivo', ['key' => $media->local_path, 'error' => $e->getMessage()]);
            }

            return;
        }

        if ($media->storage_disk === 'local' && $media->local_path) {
            Storage::disk('local')->delete($media->local_path);

            return;
        }

        if (($media->storage_disk === 'drive' || filled($media->drive_file_id)) && $media->drive_file_id && $media->driveAccount) {
            try {
                $this->driveStorage->deleteFile($media->driveAccount, $media->drive_file_id);
            } catch (\Throwable $e) {
                Log::warning('Drive: no se pudo borrar archivo', ['id' => $media->drive_file_id, 'error' => $e->getMessage()]);
            }
        }
    }
}
