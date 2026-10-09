<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use App\Models\MemoryReaction;
use App\Support\FamilySession;
use App\Support\ReactionCatalog;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request, MemoryPost $post)
    {
        $member = FamilySession::member();
        abort_unless($member, 403);

        $data = $request->validate([
            'emotion' => ['required', 'string', 'max:32'],
        ]);

        $emotion = $data['emotion'];
        if (! ReactionCatalog::isValid($emotion)) {
            return back()->with('error', 'Emoción no válida.');
        }

        $existing = MemoryReaction::query()
            ->where('memory_post_id', $post->id)
            ->where('family_member_id', $member->id)
            ->first();

        if ($existing && $existing->emotion === $emotion) {
            $existing->delete();
        } elseif ($existing) {
            $existing->update(['emotion' => $emotion]);
        } else {
            MemoryReaction::query()->create([
                'memory_post_id' => $post->id,
                'family_member_id' => $member->id,
                'emotion' => $emotion,
            ]);
        }

        $post->load(['reactions.member']);

        if ($request->expectsJson()) {
            return response()->json([
                'reactions' => $post->reactionsStateFor($member->id),
            ]);
        }

        return back();
    }
}
