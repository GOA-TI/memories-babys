<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Album;
use App\Models\DriveAccount;
use App\Services\AlbumFolderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AlbumAdminController extends Controller
{
    public function index(): View
    {
        $albums = Album::query()->with('driveAccount')->orderBy('sort_order')->get();
        $driveAccounts = DriveAccount::query()->where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.albums', compact('albums', 'driveAccounts'));
    }

    public function store(Request $request, AlbumFolderService $albumFolders)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'color' => ['nullable', 'string', 'max:32'],
            'description' => ['nullable', 'string', 'max:255'],
            'drive_account_id' => ['nullable', 'exists:drive_accounts,id'],
        ]);

        $album = Album::query()->create([
            'slug' => Album::uniqueSlug($data['title']),
            'title' => $data['title'],
            'emoji' => $data['emoji'] ?? '📁',
            'color' => $data['color'] ?? '#fbcfe8',
            'description' => $data['description'] ?? null,
            'sort_order' => (int) Album::query()->max('sort_order') + 1,
            'is_visible' => true,
        ]);

        $driveAccount = isset($data['drive_account_id'])
            ? DriveAccount::query()->find($data['drive_account_id'])
            : DriveAccount::defaultAccount();

        $message = 'Carpeta creada en la app.';
        if ($driveAccount && $driveAccount->isConfigured()) {
            try {
                $albumFolders->ensureOnDrive($album, $driveAccount);
                $message = 'Carpeta creada en la app y en Google Drive.';
            } catch (\Throwable) {
                $message = 'Carpeta creada en la app; no se pudo crear en Drive (revisa logs).';
            }
        } elseif ($driveAccount) {
            $message = 'Carpeta creada en la app. Conecta Google Drive en .env para crearla en la nube.';
        }

        return back()->with('success', $message);
    }

    public function syncDrive(Album $album, AlbumFolderService $albumFolders)
    {
        $account = $album->driveAccount ?? DriveAccount::defaultAccount();
        if (! $account || ! $account->isConfigured()) {
            return back()->with('error', 'Google Drive no está configurado.');
        }

        try {
            $albumFolders->ensureOnDrive($album->fresh(), $account);

            return back()->with('success', 'Carpeta sincronizada con Drive.');
        } catch (\Throwable) {
            return back()->with('error', 'No se pudo crear la carpeta en Drive.');
        }
    }
}
