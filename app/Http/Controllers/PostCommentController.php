<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use App\Models\MemoryPostComment;
use App\Support\FamilySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function store(Request $request, MemoryPost $post): JsonResponse|RedirectResponse
    {
        $member = FamilySession::member();
        abort_unless($member, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $body = trim(preg_replace('/\s+/u', ' ', $data['body']) ?? '');
        if ($body === '') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Escribe un comentario.'], 422);
            }

            return back()->with('error', 'Escribe un comentario.');
        }

        $post->comments()->create([
            'family_member_id' => $member->id,
            'body' => $body,
        ]);

        $post->load(['comments.member']);

        if ($request->expectsJson()) {
            return response()->json($this->jsonPayload($post, $member->id));
        }

        return back();
    }

    public function destroy(Request $request, MemoryPostComment $comment): JsonResponse|RedirectResponse
    {
        $member = FamilySession::member();
        abort_unless($member, 403);
        abort_unless($comment->family_member_id === $member->id, 403);

        $post = $comment->post;
        $comment->delete();
        $post->load(['comments.member']);

        if ($request->expectsJson()) {
            return response()->json($this->jsonPayload($post, $member->id));
        }

        return back();
    }

    /** @return array{comments: list<array>, count: int} */
    private function jsonPayload(MemoryPost $post, int $viewerMemberId): array
    {
        $comments = $post->commentsPayloadFor($viewerMemberId);

        return [
            'comments' => $comments,
            'count' => count($comments),
        ];
    }
}
