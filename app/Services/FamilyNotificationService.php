<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\FamilyNotification;
use App\Models\MemoryPost;
use App\Models\MemoryPostComment;
use App\Support\ReactionCatalog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FamilyNotificationService
{
    public function __construct(
        private FamilyWebPushService $webPush,
    ) {}

    public function notifyNewPost(MemoryPost $post, FamilyMember $author): void
    {
        $post->loadMissing(['member', 'mediaItems']);

        $recipients = FamilyMember::query()
            ->where('is_active', true)
            ->where('id', '!=', $author->id)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $authorName = $this->shortDisplayName($author);
        $title = $authorName.' publicó un recuerdo';
        $caption = trim((string) $post->caption);
        $body = $caption !== ''
            ? Str::limit($caption, 120)
            : 'Hay fotos o videos nuevos en el álbum.';

        foreach ($recipients as $recipient) {
            $this->deliverToMember(
                $recipient,
                $author,
                $post,
                FamilyNotification::TYPE_NEW_POST,
                $title,
                $body,
            );
        }
    }

    public function notifyPostComment(
        MemoryPost $post,
        FamilyMember $actor,
        MemoryPostComment $comment,
        ?MemoryPostComment $parentComment = null,
    ): void {
        $post->loadMissing(['member', 'mediaItems']);
        $actorName = $this->shortDisplayName($actor);
        $snippet = Str::limit(trim($comment->body), 120);

        $recipientIds = [];
        $postAuthorId = (int) $post->family_member_id;

        if ($parentComment !== null) {
            $parentAuthorId = (int) $parentComment->family_member_id;
            if ($parentAuthorId !== $actor->id) {
                $recipientIds[$parentAuthorId] = [
                    'title' => $actorName.' respondió tu comentario',
                    'body' => $snippet,
                ];
            }
            if ($postAuthorId !== $actor->id && $postAuthorId !== $parentAuthorId) {
                $recipientIds[$postAuthorId] = [
                    'title' => $actorName.' comentó tu recuerdo',
                    'body' => $snippet,
                ];
            }
        } elseif ($postAuthorId !== $actor->id) {
            $recipientIds[$postAuthorId] = [
                'title' => $actorName.' comentó tu recuerdo',
                'body' => $snippet,
            ];
        }

        foreach ($recipientIds as $memberId => $copy) {
            $recipient = FamilyMember::query()
                ->where('is_active', true)
                ->whereKey($memberId)
                ->first();
            if (! $recipient) {
                continue;
            }

            $this->deliverToMember(
                $recipient,
                $actor,
                $post,
                FamilyNotification::TYPE_POST_COMMENT,
                $copy['title'],
                $copy['body'],
                ['comment_id' => $comment->id],
            );
        }
    }

    public function notifyPostReaction(MemoryPost $post, FamilyMember $actor, string $emotion): void
    {
        $post->loadMissing(['member', 'mediaItems']);
        $postAuthorId = (int) $post->family_member_id;
        if ($postAuthorId === $actor->id) {
            return;
        }

        $recipient = FamilyMember::query()
            ->where('is_active', true)
            ->whereKey($postAuthorId)
            ->first();
        if (! $recipient) {
            return;
        }

        $authorName = $this->shortDisplayName($actor);
        $emoji = ReactionCatalog::emoji($emotion);
        $label = ReactionCatalog::label($emotion);
        $title = $authorName.' reaccionó a tu recuerdo';
        $body = $emoji.' '.$label;

        $this->deliverToMember(
            $recipient,
            $actor,
            $post,
            FamilyNotification::TYPE_POST_REACTION,
            $title,
            $body,
            ['emotion' => $emotion, 'emotion_emoji' => $emoji],
        );
    }

    public function urlForPost(MemoryPost $post): string
    {
        if ($post->show_on_wall) {
            return route('wall', ['post' => $post->id]);
        }

        return route('memories.index', ['highlight' => $post->id]);
    }

    private function shortDisplayName(FamilyMember $member): string
    {
        $name = trim((string) $member->name);
        if ($name === '') {
            return 'Un familiar';
        }

        return Str::before($name, ' ') ?: $name;
    }

    /** @param array<string, mixed> $metaExtra */
    private function deliverToMember(
        FamilyMember $recipient,
        FamilyMember $actor,
        MemoryPost $post,
        string $type,
        string $title,
        string $body,
        array $metaExtra = [],
    ): void {
        try {
            $url = $this->urlForPost($post);
            $thumbUrl = $this->thumbUrlForPost($post);
            $pushIcon = asset('images/pwa/icon-192.png');
            $authorName = $this->shortDisplayName($actor);

            FamilyNotification::query()->create([
                'family_member_id' => $recipient->id,
                'actor_family_member_id' => $actor->id,
                'memory_post_id' => $post->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'url' => $url,
                'meta' => array_filter(array_merge([
                    'image_url' => $thumbUrl,
                    'author_name' => $authorName,
                ], $metaExtra)),
            ]);

            $this->webPush->sendToMember(
                $recipient->id,
                $title,
                $body,
                $url,
                $pushIcon,
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar notificación familiar', [
                'recipient_id' => $recipient->id,
                'post_id' => $post->id,
                'type' => $type,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function thumbUrlForPost(MemoryPost $post): ?string
    {
        $media = $post->mediaItems->first();
        if (! $media) {
            return null;
        }

        try {
            $feed = $media->isVideo()
                ? $media->feedPosterUrl()
                : $media->feedDisplayUrl();

            return is_string($feed) && $feed !== '' ? $feed : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
