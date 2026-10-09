<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use App\Support\FamilySession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyMemoriesController extends Controller
{
    public function index(Request $request): View
    {
        $member = FamilySession::member();
        $grid = in_array($request->query('grid'), ['1', '2', '3'], true)
            ? $request->query('grid')
            : '2';

        $posts = MemoryPost::query()
            ->with(['album', 'mediaItems'])
            ->where('family_member_id', $member->id)
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('my-memories.index', compact('posts', 'grid'));
    }
}
