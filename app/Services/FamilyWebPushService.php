<?php

namespace App\Services;

use App\Models\FamilyPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class FamilyWebPushService
{
    public function isConfigured(): bool
    {
        $public = config('webpush.vapid.public_key');
        $private = config('webpush.vapid.private_key');

        return is_string($public) && $public !== ''
            && is_string($private) && $private !== '';
    }

    public function getPublicKey(): ?string
    {
        $key = config('webpush.vapid.public_key');

        return is_string($key) && $key !== '' ? $key : null;
    }

    /**
     * @return array{sent: int, failed: int}
     */
    public function sendToMember(int $memberId, string $title, string $body, string $url, ?string $iconUrl = null): array
    {
        if (! $this->isConfigured()) {
            return ['sent' => 0, 'failed' => 0];
        }

        $subscriptions = FamilyPushSubscription::query()
            ->where('family_member_id', $memberId)
            ->get();

        if ($subscriptions->isEmpty()) {
            return ['sent' => 0, 'failed' => 0];
        }

        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        $webPush = new WebPush($auth);
        $defaultIcon = asset('images/pwa/icon-192.png');
        $pushIcon = is_string($iconUrl) && $iconUrl !== '' ? $iconUrl : $defaultIcon;
        $payload = json_encode(array_filter([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'icon' => $pushIcon,
            'badge' => asset('images/pwa/icon-192.png'),
        ], fn ($v) => $v !== null && $v !== ''), JSON_UNESCAPED_UNICODE);

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $row) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $row->endpoint,
                    'publicKey' => $row->public_key,
                    'authToken' => $row->auth_token,
                    'contentEncoding' => $row->content_encoding ?: 'aesgcm',
                ]);

                $report = $webPush->sendOneNotification($subscription, $payload);

                if ($report->isSuccess()) {
                    $sent++;
                } else {
                    $failed++;
                    $statusCode = $report->getResponse()?->getStatusCode();
                    if (in_array($statusCode, [404, 410], true)) {
                        $row->delete();
                    }
                    Log::warning('Samuel web push falló', [
                        'family_member_id' => $memberId,
                        'endpoint' => $row->endpoint,
                        'reason' => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Samuel web push excepción', [
                    'family_member_id' => $memberId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
