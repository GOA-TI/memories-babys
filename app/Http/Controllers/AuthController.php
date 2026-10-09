<?php

namespace App\Http\Controllers;

use App\Models\FamilyMember;
use App\Support\FamilySession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (FamilySession::member()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:20'],
            'pin' => ['required', 'string', 'min:4', 'max:12'],
        ]);

        $member = FamilyMember::findByLogin($data['login']);

        if (! $member || ! $member->verifyPin($data['pin'])) {
            return back()->withInput()->with('error', 'Celular, cédula o clave incorrecta.');
        }

        FamilySession::login($member);

        return redirect()->intended(route('home'));
    }

    public function logout(Request $request)
    {
        FamilySession::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
