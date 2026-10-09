<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\MemoryMedia;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function index(): View
    {
        $albums = Album::query()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        $countsByAlbum = MemoryMedia::query()
            ->leftJoin('memory_posts', 'memory_posts.id', '=', 'memory_media.memory_post_id')
            ->where(function ($query) {
                $query->whereNotNull('memory_media.album_id')
                    ->orWhereNotNull('memory_posts.album_id');
            })
            ->selectRaw('COALESCE(memory_media.album_id, memory_posts.album_id) as album_id, COUNT(*) as total')
            ->groupBy('album_id')
            ->pluck('total', 'album_id');

        return view('albums.index', compact('albums', 'countsByAlbum'));
    }

    public function show(Album $album): View
    {
        abort_unless($album->is_visible, 404);

        $mediaItems = MemoryMedia::query()
            ->with(['member', 'post'])
            ->inAlbum($album)
            ->orderByDesc('memory_media.created_at')
            ->orderByDesc('memory_media.id')
            ->paginate(36);

        return view('albums.show', compact('album', 'mediaItems'));
    }
}
