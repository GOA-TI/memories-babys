<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\DriveAccount;
use App\Models\FamilyMember;
use Illuminate\Database\Seeder;

class SamuelSeeder extends Seeder
{
    public function run(): void
    {
        $this->syncDriveAccountsFromConfig();

        $cedula = FamilyMember::normalizeCedula((string) env('SAMUEL_ADMIN_CEDULA', ''));
        if ($cedula !== '') {
            $admin = FamilyMember::query()->firstOrNew(['cedula' => $cedula]);
            if (! $admin->exists) {
                $admin->name = env('SAMUEL_ADMIN_NAME', 'Papá / Mamá');
                $admin->avatar_emoji = '💛';
                $admin->setPin((string) env('SAMUEL_ADMIN_PIN', '2481'));
            }
            $admin->is_admin = true;
            $admin->is_active = true;
            $admin->save();
        }

        $albums = [
            ['slug' => 'samuel', 'title' => 'Samuel', 'emoji' => '👶', 'color' => '#fbcfe8', 'description' => 'Lo más lindo de nuestro bebé'],
            ['slug' => 'familia', 'title' => 'Familia', 'emoji' => '👨‍👩‍👦', 'color' => '#ddd6fe', 'description' => 'Todos juntos'],
            ['slug' => 'primeros', 'title' => 'Primeros momentos', 'emoji' => '🌟', 'color' => '#fde68a', 'description' => 'Hitos y primeras veces'],
            ['slug' => 'videos', 'title' => 'Videos', 'emoji' => '🎬', 'color' => '#bfdbfe', 'description' => 'Clips corticos'],
        ];

        foreach ($albums as $i => $row) {
            Album::query()->updateOrCreate(
                ['slug' => $row['slug']],
                array_merge($row, ['sort_order' => $i + 1, 'is_visible' => true])
            );
        }
    }

    public function syncDriveAccountsFromConfig(): void
    {
        $raw = config('samuel.drive_accounts_json');
        $list = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($list)) {
            return;
        }

        $defaultKey = null;
        foreach ($list as $i => $item) {
            if (! is_array($item) || empty($item['key'])) {
                continue;
            }
            $isDefault = (bool) ($item['is_default'] ?? ($i === 0));
            if ($isDefault) {
                $defaultKey = $item['key'];
            }

            DriveAccount::query()->updateOrCreate(
                ['key' => $item['key']],
                [
                    'label' => $item['label'] ?? $item['key'],
                    'google_client_id' => $item['client_id'] ?? null,
                    'google_client_secret' => $item['client_secret'] ?? null,
                    'refresh_token' => $item['refresh_token'] ?? null,
                    'root_folder_id' => $item['root_folder_id'] ?? null,
                    'owner_email' => $item['owner_email'] ?? null,
                    'is_default' => $isDefault,
                    'is_active' => (bool) ($item['is_active'] ?? true),
                    'sort_order' => $i,
                ]
            );
        }

        if ($defaultKey) {
            DriveAccount::query()->where('key', '!=', $defaultKey)->update(['is_default' => false]);
            DriveAccount::query()->where('key', $defaultKey)->update(['is_default' => true]);
        }
    }
}
