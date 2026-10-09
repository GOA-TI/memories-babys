<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SamuelProfile;
use App\Support\AppTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ThemeAdminController extends Controller
{
    public function edit(): View
    {
        $profile = SamuelProfile::current();
        $presets = AppTheme::presets();

        return view('admin.theme.edit', compact('profile', 'presets'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'preset' => ['nullable', 'string', 'in:'.implode(',', array_keys(AppTheme::presets()))],
            'theme_primary' => ['nullable', 'string', 'max:32'],
            'theme_secondary' => ['nullable', 'string', 'max:32'],
            'theme_background' => ['nullable', 'string', 'max:32'],
        ]);

        $profile = SamuelProfile::current();

        if (! empty($data['preset'])) {
            $preset = AppTheme::presets()[$data['preset']];
            $profile->update([
                'theme_primary' => $preset['primary'],
                'theme_secondary' => $preset['secondary'],
                'theme_background' => $preset['background'],
            ]);
        } else {
            $primary = AppTheme::normalizeHex($data['theme_primary'] ?? null);
            $secondary = AppTheme::normalizeHex($data['theme_secondary'] ?? null);
            $background = AppTheme::normalizeHex($data['theme_background'] ?? null);

            if (! $primary || ! $secondary || ! $background) {
                return back()->withErrors(['theme_primary' => 'Usa colores en formato #RRGGBB.'])->withInput();
            }

            $profile->update([
                'theme_primary' => $primary,
                'theme_secondary' => $secondary,
                'theme_background' => $background,
            ]);
        }

        return redirect()
            ->route('admin.theme.edit')
            ->with('success', 'Colores actualizados.');
    }
}
