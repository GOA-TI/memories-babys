<?php

namespace App\Services;

use App\Models\FamilyMember;
use App\Models\FamilyNotification;
use App\Models\MemoryPost;
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

        $authorName = trim((string) $author->name);
        if ($authorName === '') {
            $authorName = 'Un familiar';
        } else {
            $authorName = Str::before($authorName, ' ') ?: $authorName;
        }

        $title = $authorName.' publicó un recuerdo';
        $caption = trim((string) $post->caption);
        $body = $caption !== ''
            ? Str::limit($caption, 120)
            : 'Hay fotos o videos nuevos en el álbum.';

        $url = $this->urlForPost($post);
        $thumbUrl = $this->thumbUrlForPost($post);
        $pushIcon = asset('images/pwa/icon-192.png');

        foreach ($recipients as $recipient) {
            try {
                FamilyNotification::query()->create([
                    'family_member_id' => $recipient->id,
                    'actor_family_member_id' => $author->id,
                    'memory_post_id' => $post->id,
                    'type' => FamilyNotification::TYPE_NEW_POST,
                    'title' => $title,
                    'body' => $body,
                    'url' => $url,
                    'meta' => array_filter([
                        'image_url' => $thumbUrl,
                        'author_name' => $authorName,
                    ]),
                ]);

                $this->webPush->sendToMember(
                    $recipient->id,
                    $title,
                    $body,
                    $url,
                    $pushIcon,
                );
            } catch (\Throwable $e) {
                Log::warning('No se pudo notificar publicación a familiar', [
                    'recipient_id' => $recipient->id,
                    'post_id' => $post->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function urlForPost(MemoryPost $post): string
    {
        if ($post->show_on_wall) {
            return route('wall', ['post' => $post->id]);
        }

        return route('memories.index', ['highlight' => $post->id]);
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
