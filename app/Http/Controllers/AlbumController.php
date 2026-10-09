<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\MemoryPost;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function index(): View
    {
        $albums = Album::query()
            ->where('is_visible', true)
            ->withCount('posts')
            ->orderBy('sort_order')
            ->get();

        return view('albums.index', compact('albums'));
    }

    public function show(Album $album): View
    {
        abort_unless($album->is_visible, 404);

        $posts = MemoryPost::query()
            ->with(['member', 'mediaItems'])
            ->where('album_id', $album->id)
            ->latest()
            ->paginate(24);

        return view('albums.show', compact('album', 'posts'));
    }
}
