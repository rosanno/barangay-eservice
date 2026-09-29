<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * GET /api/notifications
     *
     * The most recent notifications for whoever is logged in — admin,
     * staff, or resident all use this same endpoint, since notifications
     * are personal to the account, not the role.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $notifications->map(fn(DatabaseNotification $n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? 'Notification',
                'tracking_number' => $n->data['tracking_number'] ?? null,
                'status' => $n->data['status'] ?? null,
                'read' => $n->read_at !== null,
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * POST /api/notifications/{notification}/read
     * Laravel resolves DatabaseNotification via route-model-binding on its
     * uuid primary key automatically — no custom binding needed.
     */
    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        if ($notification->notifiable_id !== $request->user()->id) {
            abort(403);
        }

        $notification->markAsRead();

        return response()->json(['data' => ['id' => $notification->id, 'read' => true]]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['data' => ['marked' => true]]);
    }
}