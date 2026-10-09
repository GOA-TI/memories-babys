<?php

namespace App\Services;

use App\Models\DriveAccount;
use Google\Client as GoogleClient;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleDriveStorage
{
    public function clientFor(DriveAccount $account): GoogleClient
    {
        if (! $account->isConfigured()) {
            throw new RuntimeException('La cuenta Drive «'.$account->label.'» no tiene OAuth configurado.');
        }

        $client = new GoogleClient;
        $client->setApplicationName(config('app.name'));
        $client->setScopes([Drive::DRIVE_FILE]);
        $client->setAccessType('offline');
        $client->setClientId($account->google_client_id);
        $client->setClientSecret($account->google_client_secret);
        $client->setPrompt('consent');
        $client->fetchAccessTokenWithRefreshToken($account->refresh_token);

        return $client;
    }

    public function driveService(DriveAccount $account): Drive
    {
        return new Drive($this->clientFor($account));
    }

    public function ensureFolder(DriveAccount $account, string $folderName, ?string $parentId = null): string
    {
        $parentId = $parentId ?: $account->root_folder_id;
        if (! $parentId) {
            throw new RuntimeException('Indica root_folder_id para la cuenta Drive «'.$account->label.'».');
        }

        $drive = $this->driveService($account);
        $escaped = str_replace("'", "\\'", $folderName);
        $q = "mimeType='application/vnd.google-apps.folder' and name='{$escaped}' and '{$parentId}' in parents and trashed=false";
        $existing = $drive->files->listFiles([
            'q' => $q,
            'fields' => 'files(id,name)',
            'pageSize' => 1,
        ]);

        if ($existing->getFiles()) {
            return $existing->getFiles()[0]->getId();
        }

        $meta = new DriveFile([
            'name' => $folderName,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$parentId],
        ]);

        $created = $drive->files->create($meta, ['fields' => 'id']);

        return $created->getId();
    }

    /**
     * @return array{file_id: string, web_view_link: ?string, thumbnail_link: ?string}
     */
    public function upload(DriveAccount $account, string $parentFolderId, UploadedFile $file, string $targetName): array
    {
        $drive = $this->driveService($account);

        $meta = new DriveFile([
            'name' => $targetName,
            'parents' => [$parentFolderId],
        ]);

        $created = $drive->files->create($meta, [
            'data' => file_get_contents($file->getRealPath()),
            'mimeType' => $file->getMimeType() ?: 'application/octet-stream',
            'uploadType' => 'multipart',
            'fields' => 'id,webViewLink,thumbnailLink',
        ]);

        try {
            $drive->permissions->create($created->getId(), new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]));
        } catch (\Throwable $e) {
            Log::warning('Drive: no se pudo hacer público el archivo', ['id' => $created->getId(), 'error' => $e->getMessage()]);
        }

        return [
            'file_id' => $created->getId(),
            'web_view_link' => $created->getWebViewLink(),
            'thumbnail_link' => $created->getThumbnailLink(),
        ];
    }

    public function deleteFile(DriveAccount $account, string $fileId): void
    {
        $this->driveService($account)->files->delete($fileId);
    }
}
