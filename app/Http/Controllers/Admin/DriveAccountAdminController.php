<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DriveAccount;
use App\Services\GoogleDriveStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DriveAccountAdminController extends Controller
{
    public function index(): View
    {
        $accounts = DriveAccount::query()->orderBy('sort_order')->orderBy('id')->get();

        return view('admin.drive-accounts', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, requiredSecrets: true);

        $key = Str::slug($data['key'] ?: $data['label']) ?: 'drive-'.Str::lower(Str::random(4));

        if (DriveAccount::query()->where('key', $key)->exists()) {
            $key .= '-'.Str::lower(Str::random(3));
        }

        $account = DriveAccount::query()->create([
            'key' => $key,
            'label' => $data['label'],
            'owner_email' => $data['owner_email'] ?? null,
            'google_client_id' => $data['google_client_id'],
            'google_client_secret' => $data['google_client_secret'],
            'refresh_token' => $data['refresh_token'],
            'root_folder_id' => $data['root_folder_id'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_active' => true,
            'sort_order' => (int) DriveAccount::query()->max('sort_order') + 1,
        ]);

        $this->applyDefaultFlag($account, (bool) ($data['is_default'] ?? false));

        return back()->with('success', 'Cuenta Drive guardada.');
    }

    public function update(Request $request, DriveAccount $driveAccount)
    {
        $data = $this->validated($request, requiredSecrets: false);

        $payload = [
            'label' => $data['label'],
            'owner_email' => $data['owner_email'] ?? null,
            'google_client_id' => $data['google_client_id'],
            'root_folder_id' => $data['root_folder_id'] ?? null,
            'is_default' => $request->boolean('is_default'),
        ];

        if (filled($data['google_client_secret'] ?? null)) {
            $payload['google_client_secret'] = $data['google_client_secret'];
        }
        if (filled($data['refresh_token'] ?? null)) {
            $payload['refresh_token'] = $data['refresh_token'];
        }

        $driveAccount->update($payload);
        $this->applyDefaultFlag($driveAccount->fresh(), $request->boolean('is_default'));

        return back()->with('success', 'Cuenta Drive actualizada.');
    }

    public function test(DriveAccount $driveAccount, GoogleDriveStorage $drive)
    {
        $driveAccount->refresh();
        if (! $driveAccount->isConfigured()) {
            return back()->with('error', 'Faltan client_id, client_secret o refresh_token.');
        }
        if (! filled($driveAccount->root_folder_id)) {
            return back()->with('error', 'Indica root_folder_id (ID de la carpeta raíz en Drive).');
        }

        try {
            $service = $drive->driveService($driveAccount);
            $service->files->get($driveAccount->root_folder_id, ['fields' => 'id,name,mimeType']);

            return back()->with('success', 'Conexión OK: la carpeta raíz existe en Drive.');
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo conectar: '.$e->getMessage());
        }
    }

    private function validated(Request $request, bool $requiredSecrets): array
    {
        $rules = [
            'label' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:60'],
            'owner_email' => ['nullable', 'email', 'max:190'],
            'google_client_id' => ['required', 'string', 'max:255'],
            'google_client_secret' => [$requiredSecrets ? 'required' : 'nullable', 'string', 'max:255'],
            'refresh_token' => [$requiredSecrets ? 'required' : 'nullable', 'string', 'max:512'],
            'root_folder_id' => ['nullable', 'string', 'max:120'],
            'is_default' => ['nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }

    private function applyDefaultFlag(DriveAccount $account, bool $isDefault): void
    {
        if (! $isDefault) {
            return;
        }

        DriveAccount::query()->where('id', '!=', $account->id)->update(['is_default' => false]);
        $account->update(['is_default' => true]);
    }
}
