<?php

namespace App\Services;

use App\Models\Album;
use App\Models\DriveAccount;
use Illuminate\Support\Facades\Log;

class AlbumFolderService
{
    public function __construct(
        private readonly GoogleDriveStorage $driveStorage,
    ) {}

    public function driveFolderName(Album $album): string
    {
        $emoji = trim((string) $album->emoji);
        $title = trim((string) $album->title);

        return $emoji !== '' ? $emoji.' '.$title : $title;
    }

    /**
     * Crea (o reutiliza) la carpeta del álbum en Google Drive y guarda drive_folder_id.
     */
    public function ensureOnDrive(Album $album, ?DriveAccount $account = null): Album
    {
        if ($album->drive_folder_id) {
            return $album;
        }

        $account ??= $album->driveAccount ?? DriveAccount::defaultAccount();
        if (! $account || ! $account->isConfigured()) {
            return $album;
        }

        try {
            $folderId = $this->driveStorage->ensureFolder(
                $account,
                $this->driveFolderName($album),
                $account->root_folder_id
            );

            $album->update([
                'drive_folder_id' => $folderId,
                'drive_account_id' => $account->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('No se pudo crear carpeta en Drive', [
                'album_id' => $album->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $album->fresh();
    }
}
