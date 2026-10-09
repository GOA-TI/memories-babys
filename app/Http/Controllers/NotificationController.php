<?php

namespace App\Http\Controllers;

use App\Models\FamilyNotification;
use App\Models\FamilyPushSubscription;
use App\Services\FamilyWebPushService;
use App\Support\FamilySession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private FamilyWebPushService $webPush,
    ) {}

    public function index(): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $notifications = FamilyNotification::query()
            ->where('family_member_id', $member->id)
            ->orderByDesc('created_at')
            ->limit(40)
            ->get();

        $unreadCount = FamilyNotification::query()
            ->where('family_member_id', $member->id)
            ->whereNull('read_at')
            ->count();

        $payload = $notifications->map(function (FamilyNotification $n) {
            return [
                'id' => $n->id,
                'type' => $n->type,
                'title' => $n->title,
                'body' => $n->body,
                'url' => $n->url,
                'image_url' => $n->meta['image_url'] ?? null,
                'is_unread' => $n->isUnread(),
                'created_at' => $n->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'notifications' => $payload,
            'unread_count' => $unreadCount,
            'push_configured' => $this->webPush->isConfigured(),
            'vapid_public_key' => $this->webPush->getPublicKey(),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $notification = FamilyNotification::query()
            ->where('family_member_id', $member->id)
            ->where('id', $id)
            ->first();

        if (! $notification) {
            return response()->json(['success' => false, 'message' => 'No encontrada'], 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['success' => true]);
    }

    public function markAllRead(): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        FamilyNotification::query()
            ->where('family_member_id', $member->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function vapidPublic(): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $key = $this->webPush->getPublicKey();
        if (! $key) {
            return response()->json([
                'success' => false,
                'message' => 'Las alertas push no están configuradas en el servidor.',
            ], 503);
        }

        return response()->json(['success' => true, 'public_key' => $key]);
    }

    public function subscribePush(Request $request): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string|max:32',
        ]);

        try {
            FamilyPushSubscription::query()->updateOrCreate(
                ['endpoint' => $validated['endpoint']],
                [
                    'family_member_id' => $member->id,
                    'public_key' => $validated['keys']['p256dh'],
                    'auth_token' => $validated['keys']['auth'],
                    'content_encoding' => $validated['contentEncoding'] ?? 'aesgcm',
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                ]
            );
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo guardar la suscripción push.',
            ], 503);
        }

        return response()->json(['success' => true, 'message' => 'Alertas activadas en este dispositivo']);
    }

    public function unsubscribePush(Request $request): JsonResponse
    {
        $member = FamilySession::member();
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
        ]);

        FamilyPushSubscription::query()
            ->where('family_member_id', $member->id)
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['success' => true, 'message' => 'Alertas desactivadas']);
    }
}
