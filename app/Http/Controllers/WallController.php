<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use Illuminate\View\View;

class WallController extends Controller
{
    public function __invoke(): View
    {
        $posts = MemoryPost::query()
            ->with(['member', 'album', 'mediaItems', 'reactions.member', 'comments.member'])
            ->where('show_on_wall', true)
            ->latest()
            ->paginate(20);

        return view('wall', compact('posts'));
    }
}
