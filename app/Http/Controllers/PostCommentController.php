<?php

namespace App\Http\Controllers;

use App\Models\MemoryPost;
use App\Models\MemoryPostComment;
use App\Services\FamilyNotificationService;
use App\Support\FamilySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function store(
        Request $request,
        MemoryPost $post,
        FamilyNotificationService $notifier,
    ): JsonResponse|RedirectResponse {
        $member = FamilySession::member();
        abort_unless($member, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'parent_comment_id' => ['nullable', 'integer', 'exists:memory_post_comments,id'],
        ]);

        $body = trim(preg_replace('/\s+/u', ' ', $data['body']) ?? '');
        if ($body === '') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Escribe un comentario.'], 422);
            }

            return back()->with('error', 'Escribe un comentario.');
        }

        $parentId = isset($data['parent_comment_id']) ? (int) $data['parent_comment_id'] : null;
        $parent = null;
        if ($parentId) {
            $parent = MemoryPostComment::query()
                ->where('memory_post_id', $post->id)
                ->whereKey($parentId)
                ->first();
            if (! $parent) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'No se pudo responder a ese comentario.'], 422);
                }

                return back()->with('error', 'No se pudo responder a ese comentario.');
            }
        }

        $comment = $post->comments()->create([
            'family_member_id' => $member->id,
            'parent_comment_id' => $parentId,
            'body' => $body,
        ]);

        $notifier->notifyPostComment($post, $member, $comment, $parent);

        $this->loadComments($post);

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
        $this->loadComments($post);

        if ($request->expectsJson()) {
            return response()->json($this->jsonPayload($post, $member->id));
        }

        return back();
    }

    private function loadComments(MemoryPost $post): void
    {
        $post->load(['comments.member', 'comments.parent.member']);
    }

    /** @return array{comments: list<array>, count: int} */
    private function jsonPayload(MemoryPost $post, int $viewerMemberId): array
    {
        $comments = $post->commentsPayloadFor($viewerMemberId);
        $count = $post->commentsCount();

        return [
            'comments' => $comments,
            'count' => $count,
        ];
    }
}
