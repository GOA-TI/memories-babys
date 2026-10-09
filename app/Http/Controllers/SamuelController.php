<?php

namespace App\Http\Controllers;

use App\Models\SamuelProfile;
use Illuminate\View\View;

class SamuelController extends Controller
{
    public function show(): View
    {
        $profile = SamuelProfile::current();

        return view('samuel.show', compact('profile'));
    }
}
