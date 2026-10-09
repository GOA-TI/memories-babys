<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FamilyMember;
use App\Services\MemberAvatarService;
use App\Support\AvatarPhotoRules;
use App\Support\FamilyRelationship;
use App\Support\FamilySession;
use Illuminate\Http\Request;
use RuntimeException;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MemberAdminController extends Controller
{
    public function index(): View
    {
        $members = FamilyMember::query()->orderBy('name')->get();

        return view('admin.members.index', compact('members'));
    }

    public function create(): View
    {
        return $this->memberFormView(null);
    }

    public function edit(FamilyMember $member): View
    {
        return $this->memberFormView($member);
    }

    private function memberFormView(?FamilyMember $member): View
    {
        return view('admin.members.form', [
            'member' => $member,
            'relationships' => FamilyRelationship::options(),
            'isCreate' => $member === null,
        ]);
    }

    public function store(Request $request, MemberAvatarService $avatars)
    {
        $relationshipKeys = array_keys(FamilyRelationship::options());

        $data = $request->validate([
            'cedula' => ['required', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
            'name' => ['required', 'string', 'max:120'],
            'relationship' => ['nullable', 'string', Rule::in(array_merge([''], $relationshipKeys))],
            'pin' => ['required', 'string', 'min:4', 'max:12'],
            'is_admin' => ['nullable', 'boolean'],
            'avatar_emoji' => ['nullable', 'string', 'max:8'],
            'avatar_photo' => AvatarPhotoRules::validation(),
        ]);

        $cedula = FamilyMember::normalizeCedula($data['cedula']);
        if ($cedula === '') {
            return back()->with('error', 'Cédula inválida.');
        }

        $phone = isset($data['phone']) ? FamilyMember::normalizePhone($data['phone']) : null;
        if ($phone === '') {
            $phone = null;
        }

        if (FamilyMember::query()->where('cedula', $cedula)->exists()) {
            return back()->with('error', 'Esa cédula ya está registrada.');
        }

        if ($phone && FamilyMember::query()->where('phone', $phone)->exists()) {
            return back()->with('error', 'Ese celular ya está registrado.');
        }

        $relationship = $data['relationship'] ?? null;
        if ($relationship === '') {
            $relationship = null;
        }

        $member = new FamilyMember([
            'cedula' => $cedula,
            'phone' => $phone,
            'name' => $data['name'],
            'relationship' => $relationship,
            'is_admin' => (bool) ($data['is_admin'] ?? false),
            'avatar_emoji' => $data['avatar_emoji'] ?? '🙂',
            'is_active' => true,
        ]);
        $member->setPin($data['pin']);
        $member->save();

        try {
            if ($request->hasFile('avatar_photo')) {
                $avatars->store($member, $request->file('avatar_photo'));
            }
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['avatar_photo' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Usuario agregado.')
            ->with('pin_reveal', [
                'cedula' => $member->cedula,
                'name' => $member->name,
                'pin' => $data['pin'],
            ]);
    }

    public function update(Request $request, FamilyMember $member, MemberAvatarService $avatars)
    {
        $relationshipKeys = array_keys(FamilyRelationship::options());

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:20'],
            'relationship' => ['nullable', 'string', Rule::in(array_merge([''], $relationshipKeys))],
            'name' => ['required', 'string', 'max:120'],
            'avatar_emoji' => ['nullable', 'string', 'max:8'],
            'avatar_photo' => AvatarPhotoRules::validation(),
            'remove_avatar_photo' => ['nullable', 'boolean'],
            'is_admin' => ['nullable', 'boolean'],
            'pin' => ['nullable', 'string', 'min:4', 'max:12'],
        ]);

        $willBeAdmin = (bool) ($data['is_admin'] ?? false);
        if ($this->wouldRemoveLastAdmin($member, $willBeAdmin, $member->is_active)) {
            return back()->with('error', 'Debe quedar al menos un administrador activo.');
        }

        $phone = isset($data['phone']) ? FamilyMember::normalizePhone($data['phone']) : null;
        if ($phone === '') {
            $phone = null;
        }

        if ($phone && FamilyMember::query()->where('phone', $phone)->where('id', '!=', $member->id)->exists()) {
            return back()->with('error', 'Ese celular ya lo usa otra persona.');
        }

        $relationship = $data['relationship'] ?? null;
        if ($relationship === '') {
            $relationship = null;
        }

        $member->update([
            'name' => $data['name'],
            'phone' => $phone,
            'relationship' => $relationship,
            'avatar_emoji' => $data['avatar_emoji'] ?? $member->avatar_emoji,
            'is_admin' => $willBeAdmin,
        ]);

        try {
            if ($request->boolean('remove_avatar_photo')) {
                $avatars->remove($member);
            } elseif ($request->hasFile('avatar_photo')) {
                $avatars->store($member, $request->file('avatar_photo'));
            }
        } catch (RuntimeException $e) {
            return back()->withInput()->withErrors(['avatar_photo' => $e->getMessage()]);
        }

        $pinReveal = null;
        if (! empty($data['pin'])) {
            $member->setPin($data['pin']);
            $member->save();
            $pinReveal = $data['pin'];
        }

        $redirect = redirect()->route('admin.members.index')->with('success', 'Usuario actualizado.');
        if ($pinReveal !== null) {
            $redirect->with('pin_reveal', [
                'cedula' => $member->cedula,
                'name' => $member->name,
                'pin' => $pinReveal,
            ]);
        }

        return $redirect;
    }

    public function resetPin(FamilyMember $member)
    {
        $pin = (string) random_int(1000, 9999);
        $member->setPin($pin);
        $member->save();

        return back()
            ->with('success', 'Nueva clave generada.')
            ->with('pin_reveal', [
                'cedula' => $member->cedula,
                'name' => $member->name,
                'pin' => $pin,
            ]);
    }

    public function toggle(FamilyMember $member)
    {
        $willBeActive = ! $member->is_active;
        if ($this->wouldRemoveLastAdmin($member, $member->is_admin, $willBeActive)) {
            return back()->with('error', 'No puedes desactivar al último administrador.');
        }

        $member->update(['is_active' => $willBeActive]);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(FamilyMember $member, MemberAvatarService $avatars): RedirectResponse
    {
        $current = FamilySession::member();
        if ($current && $current->id === $member->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario mientras estás conectada.');
        }

        if ($this->wouldRemoveLastAdmin($member, false, false)) {
            return back()->with('error', 'Debe quedar al menos un administrador activo.');
        }

        $avatars->remove($member);
        $member->delete();

        return redirect()
            ->route('admin.members.index')
            ->with('success', 'Usuario eliminado por completo.');
    }

    private function wouldRemoveLastAdmin(FamilyMember $member, bool $willBeAdmin, bool $willBeActive): bool
    {
        if (! $member->is_admin) {
            return false;
        }

        if ($willBeAdmin && $willBeActive) {
            return false;
        }

        $otherActiveAdmins = FamilyMember::query()
            ->where('is_admin', true)
            ->where('is_active', true)
            ->where('id', '!=', $member->id)
            ->count();

        return $otherActiveAdmins === 0;
    }
}
