<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SamuelProfile;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
class SamuelAdminController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->to(route('samuel.show').'#samuel-config');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['nullable', 'string', 'max:80'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'birth_time' => ['nullable', 'date_format:H:i'],
        ]);

        $tz = config('app.timezone');
        $time = filled($data['birth_time'] ?? null) ? $data['birth_time'] : '00:00';
        $birthAt = Carbon::createFromFormat('Y-m-d H:i', $data['birth_date'].' '.$time, $tz);

        if ($birthAt->isFuture()) {
            return back()->withErrors(['birth_date' => 'La fecha de nacimiento no puede ser en el futuro.'])->withInput();
        }

        $profile = SamuelProfile::current();
        $profile->update([
            'display_name' => filled($data['display_name'] ?? null) ? $data['display_name'] : null,
            'emoji' => filled($data['emoji'] ?? null) ? $data['emoji'] : '👶',
            'birth_at' => $birthAt,
        ]);

        return redirect()
            ->route('samuel.show')
            ->with('success', 'Datos de '.$profile->displayName().' guardados.');
    }
}
