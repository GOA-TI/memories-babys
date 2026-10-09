<?php

namespace App\Services;

use App\Models\Album;
use App\Models\DriveAccount;
use App\Models\FamilyMember;
use App\Models\MemoryMedia;
use App\Models\MemoryPost;
use App\Support\PreparedUploadBinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    public function __construct(
        private readonly GoogleDriveStorage $driveStorage,
        private readonly AlbumFolderService $albumFolders,
        private readonly R2StorageService $r2Storage,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function storePost(
        FamilyMember $member,
        array $files,
        ?Album $album,
        ?string $caption,
        bool $showOnWall = true,
        ?DriveAccount $driveAccount = null,
        ?\Carbon\CarbonInterface $memoryAt = null,
    ): MemoryPost {
        return DB::transaction(function () use ($member, $files, $album, $caption, $showOnWall, $driveAccount, $memoryAt) {
            $post = MemoryPost::query()->create([
                'family_member_id' => $member->id,
                'album_id' => $album?->id,
                'caption' => $caption,
                'memory_at' => $memoryAt ?? now(),
                'show_on_wall' => $showOnWall,
            ]);

            foreach (array_values($files) as $index => $file) {
                $this->storeFileOnPost($post, $member, $file, $album, $index, $driveAccount);
            }

            return $post->load('mediaItems');
        });
    }

    public function store(
        FamilyMember $member,
        UploadedFile $file,
        ?Album $album,
        ?string $caption,
        bool $showOnWall = true,
        ?DriveAccount $driveAccount = null,
    ): MemoryMedia {
        $post = $this->storePost($member, [$file], $album, $caption, $showOnWall, $driveAccount);

        return $post->mediaItems->first();
    }

    private function storeFileOnPost(
        MemoryPost $post,
        FamilyMember $member,
        UploadedFile $file,
        ?Album $album,
        int $sortOrder,
        ?DriveAccount $driveAccount = null,
    ): MemoryMedia {
        $driveAccount ??= $album?->driveAccount ?? DriveAccount::defaultAccount();

        $uploadCtx = $this->resolveUploadContext($file);
        $uploadFile = $uploadCtx['upload_file'];
        $targetName = $uploadCtx['target_name'].'-'.now()->format('Ymd-His').'-'.$sortOrder.'.'.$uploadCtx['extension'];

        $payload = [
            'memory_post_id' => $post->id,
            'sort_order' => $sortOrder,
            'family_member_id' => $member->id,
            'album_id' => $album?->id,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $uploadCtx['mime_type'],
            'caption' => null,
            'show_on_wall' => $post->show_on_wall,
            'bytes' => $uploadCtx['bytes'],
        ];

        $driver = $this->resolveStorageDriver($driveAccount);
        $stored = $this->persistUpload($driver, $uploadCtx, $targetName, $album, $driveAccount);
        $payload = array_merge($payload, $stored);

        $memory = MemoryMedia::query()->create($payload);

        if ($memory->isVideo()) {
            app(MediaVideoPosterService::class)->generateFromUpload($uploadFile, $memory);
        }

        return $memory;
    }

    public function updatePostMetadata(
        MemoryPost $post,
        ?string $caption,
        ?Album $album,
        bool $showOnWall,
        ?\Carbon\CarbonInterface $memoryAt = null,
    ): MemoryPost {
        $payload = [
            'caption' => $caption,
            'album_id' => $album?->id,
            'show_on_wall' => $showOnWall,
        ];
        if ($memoryAt !== null) {
            $payload['memory_at'] = $memoryAt;
        }
        $post->update($payload);

        MemoryMedia::query()
            ->where('memory_post_id', $post->id)
            ->update([
                'album_id' => $album?->id,
                'show_on_wall' => $showOnWall,
            ]);

        return $post->fresh(['mediaItems']);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public function appendFilesToPost(
        MemoryPost $post,
        array $files,
        ?Album $album,
        ?DriveAccount $driveAccount = null,
    ): MemoryPost {
        $member = $post->member;
        $album ??= $post->album;
        $nextOrder = (int) MemoryMedia::query()->where('memory_post_id', $post->id)->max('sort_order') + 1;

        DB::transaction(function () use ($post, $member, $files, $album, &$nextOrder, $driveAccount) {
            foreach ($files as $file) {
                $this->storeFileOnPost($post, $member, $file, $album, $nextOrder, $driveAccount);
                $nextOrder++;
            }
        });

        return $post->fresh(['mediaItems']);
    }

    public function replaceFile(
        MemoryMedia $memory,
        UploadedFile $file,
        ?Album $album,
        MediaDeleteService $deleter,
    ): MemoryMedia {
        $deleter->deleteStoredFile($memory);

        $driveAccount = $memory->driveAccount ?? $album?->driveAccount ?? DriveAccount::defaultAccount();

        $uploadCtx = $this->resolveUploadContext($file);
        $uploadFile = $uploadCtx['upload_file'];
        $targetName = $uploadCtx['target_name'].'-'.now()->format('Ymd-His').'.'.$uploadCtx['extension'];

        $payload = [
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $uploadCtx['mime_type'],
            'bytes' => $uploadCtx['bytes'],
            'drive_file_id' => null,
            'drive_web_view_link' => null,
            'drive_thumbnail_link' => null,
            'public_url' => null,
        ];

        $driver = $this->resolveStorageDriver($driveAccount);

        $stored = $this->persistUpload($driver, $uploadCtx, $targetName, $album, $driveAccount);
        $payload = array_merge($payload, $stored);

        $memory->update($payload);
        $memory = $memory->fresh();

        if ($memory->isVideo()) {
            app(MediaVideoPosterService::class)->generateFromUpload($uploadFile, $memory);
        }

        return $memory;
    }

    private function resolveStorageDriver(?DriveAccount $driveAccount): string
    {
        $pref = strtolower((string) config('samuel.storage_driver', 'auto'));

        if ($pref === 'local') {
            return 'local';
        }
        if ($pref === 'drive') {
            return ($driveAccount && $driveAccount->isConfigured()) ? 'drive' : 'local';
        }
        if ($pref === 'r2') {
            return $this->r2Storage->isConfigured() ? 'r2' : 'local';
        }

        if ($this->r2Storage->isConfigured()) {
            return 'r2';
        }
        if ($driveAccount && $driveAccount->isConfigured()) {
            return 'drive';
        }

        return 'local';
    }

    private function temporaryUploadedFile(PreparedUploadBinary $prepared): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'samuel-up-');
        if ($tmp === false) {
            throw new \RuntimeException('No se pudo preparar la foto.');
        }
        file_put_contents($tmp, $prepared->contents);

        return new UploadedFile($tmp, $prepared->filename, $prepared->mimeType, null, true);
    }

    /**
     * @return array{
     *     upload_file: UploadedFile,
     *     target_name: string,
     *     extension: string,
     *     mime_type: string,
     *     bytes: int,
     *     prepared: ?PreparedUploadBinary
     * }
     */
    private function resolveUploadContext(UploadedFile $file): array
    {
        $normalizer = app(MemoryImageUploadNormalizer::class);
        $prepared = null;
        $uploadFile = $file;

        if ($normalizer->needsHeicConversion($file)) {
            $prepared = $normalizer->prepare($file);
            $uploadFile = $this->temporaryUploadedFile($prepared);
        }

        $nameForSlug = $prepared?->filename ?? $file->getClientOriginalName();
        $safeName = Str::slug(pathinfo((string) $nameForSlug, PATHINFO_FILENAME)) ?: 'recuerdo';
        $ext = pathinfo((string) ($prepared?->filename ?? ''), PATHINFO_EXTENSION)
            ?: ($uploadFile->getClientOriginalExtension() ?: $uploadFile->extension() ?: 'bin');

        return [
            'upload_file' => $uploadFile,
            'target_name' => $safeName,
            'extension' => $ext,
            'mime_type' => $prepared?->mimeType ?? ($file->getMimeType() ?: 'application/octet-stream'),
            'bytes' => $prepared !== null ? strlen($prepared->contents) : (int) $file->getSize(),
            'prepared' => $prepared,
        ];
    }

    /**
     * @param  array{upload_file: UploadedFile, prepared: ?PreparedUploadBinary, mime_type: string}  $uploadCtx
     * @return array<string, mixed>
     */
    private function persistUpload(
        string $driver,
        array $uploadCtx,
        string $targetName,
        ?Album $album,
        ?DriveAccount $driveAccount,
    ): array {
        $uploadFile = $uploadCtx['upload_file'];
        $prepared = $uploadCtx['prepared'];

        if ($driver === 'r2') {
            if ($prepared !== null) {
                $uploaded = $this->r2Storage->uploadContents(
                    $prepared->contents,
                    $targetName,
                    $prepared->mimeType,
                    $album?->slug,
                );
            } else {
                $uploaded = $this->r2Storage->upload($uploadFile, $targetName, $album?->slug);
            }

            return [
                'storage_disk' => 'r2',
                'local_path' => $uploaded['key'],
                'public_url' => $uploaded['public_url'],
            ];
        }

        if ($driver === 'drive') {
            if ($album && $driveAccount) {
                $album = $this->albumFolders->ensureOnDrive($album, $driveAccount);
            }
            $parent = $album?->drive_folder_id ?: $driveAccount?->root_folder_id;
            $uploaded = $this->driveStorage->upload($driveAccount, (string) $parent, $uploadFile, $targetName);

            return [
                'storage_disk' => 'drive',
                'drive_account_id' => $driveAccount?->id,
                'drive_file_id' => $uploaded['file_id'],
                'drive_web_view_link' => $uploaded['web_view_link'],
                'drive_thumbnail_link' => $uploaded['thumbnail_link'],
                'local_path' => null,
            ];
        }

        if ($prepared !== null) {
            $path = 'memories/'.now()->format('Y/m').'/'.$targetName;
            Storage::disk('local')->put($path, $prepared->contents);

            return [
                'storage_disk' => 'local',
                'local_path' => $path,
            ];
        }

        $path = $uploadFile->storeAs('memories/'.now()->format('Y/m'), $targetName, 'local');

        return [
            'storage_disk' => 'local',
            'local_path' => $path,
        ];
    }
}
