<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\R2StorageService;
use Illuminate\View\View;

class R2AdminController extends Controller
{
    public function index(): View
    {
        $checks = [
            'R2_ACCESS_KEY_ID' => filled(config('filesystems.disks.r2.key')),
            'R2_SECRET_ACCESS_KEY' => filled(config('filesystems.disks.r2.secret')),
            'R2_BUCKET' => filled(config('filesystems.disks.r2.bucket')),
            'R2_ENDPOINT' => filled(config('filesystems.disks.r2.endpoint')),
            'R2_PUBLIC_URL' => filled(config('filesystems.disks.r2.url')),
        ];

        return view('admin.r2', [
            'checks' => $checks,
            'bucket' => config('filesystems.disks.r2.bucket'),
            'endpoint' => config('filesystems.disks.r2.endpoint'),
            'publicUrl' => config('filesystems.disks.r2.url'),
            'storageDriver' => config('samuel.storage_driver'),
        ]);
    }

    public function test(R2StorageService $r2)
    {
        if (! $r2->isConfigured()) {
            return back()->with('error', 'Completa las variables R2 en el .env del servidor.');
        }

        try {
            $r2->testConnection();
        } catch (\Throwable $e) {
            return back()->with('error', 'Error R2: '.$e->getMessage());
        }

        if (! $r2->hasPublicUrl()) {
            return back()->with('success', 'Subida OK. Falta R2_PUBLIC_URL para ver fotos en el muro.');
        }

        return back()->with('success', 'Cloudflare R2 conectado correctamente.');
    }
}
