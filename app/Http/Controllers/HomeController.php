<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $favorites = MemoryPost::query()
            ->with(['member', 'album', 'mediaItems'])
            ->where('is_favorite', true)
            ->latest()
            ->limit(12)
            ->get();

        $latest = MemoryPost::query()
            ->with(['member', 'album', 'mediaItems'])
            ->latest()
            ->limit(16)
            ->get();

        return view('home', compact('favorites', 'latest'));
    }
}
