<?php

namespace App\Services;

use App\Models\FamilyMember;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MemberAvatarService
{
    public function __construct(
        private readonly R2StorageService $r2Storage,
        private readonly AvatarImageProcessor $images,
    ) {}

    public function store(FamilyMember $member, UploadedFile $file): void
    {
        $this->deleteStored($member);

        $jpeg = $this->images->toJpegBytes($file);

        $safe = Str::slug($member->name) ?: 'familia';
        $targetName = $safe.'-avatar-'.now()->format('Ymd-His').'.jpg';

        if ($this->r2Storage->isConfigured()) {
            $key = 'memorias/avatares/'.now()->format('Y/m').'/'.$targetName;
            Storage::disk('r2')->put($key, $jpeg, [
                'visibility' => 'public',
                'ContentType' => 'image/jpeg',
            ]);
            $member->update([
                'avatar_path' => $key,
                'avatar_disk' => 'r2',
            ]);

            return;
        }

        $relative = 'avatars/'.now()->format('Y').'/'.$targetName;
        Storage::disk('local')->put($relative, $jpeg);
        $member->update([
            'avatar_path' => $relative,
            'avatar_disk' => 'local',
        ]);
    }

    /** Convierte avatares HEIC antiguos a JPEG (mantenimiento). */
    public function reprocessHeicToJpeg(FamilyMember $member): bool
    {
        if (! $member->hasAvatarPhoto()) {
            return false;
        }

        $path = (string) $member->avatar_path;
        if (! str_ends_with(strtolower($path), '.heic') && ! str_ends_with(strtolower($path), '.heif')) {
            return false;
        }

        $disk = $member->avatar_disk ?: 'local';
        $storage = $disk === 'r2' ? Storage::disk('r2') : Storage::disk('local');
        if (! $storage->exists($path)) {
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'samuel_heic_');
        if ($tmp === false) {
            return false;
        }

        file_put_contents($tmp, $storage->get($path));

        try {
            $processor = app(AvatarImageProcessor::class);
            $uploaded = new UploadedFile($tmp, basename($path), 'image/heic', null, true);
            $this->store($member, $uploaded);
        } finally {
            @unlink($tmp);
        }

        return true;
    }

    public function remove(FamilyMember $member): void
    {
        $this->deleteStored($member);
        $member->update([
            'avatar_path' => null,
            'avatar_disk' => null,
        ]);
    }

    public function deleteStored(FamilyMember $member): void
    {
        if (! $member->avatar_path) {
            return;
        }

        $disk = $member->avatar_disk ?: 'local';

        try {
            if ($disk === 'r2') {
                Storage::disk('r2')->delete($member->avatar_path);
            } elseif ($disk === 'local') {
                Storage::disk('local')->delete($member->avatar_path);
            }
        } catch (\Throwable $e) {
            Log::warning('Avatar: no se pudo borrar', ['path' => $member->avatar_path, 'error' => $e->getMessage()]);
        }
    }
}
