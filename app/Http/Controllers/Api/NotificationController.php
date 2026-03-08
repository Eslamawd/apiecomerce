<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage       = min((int) $request->get('per_page', 15), 100);
        $notifications = $request->user()
            ->notifications()
            ->paginate($perPage);

        return response()->json($notifications->through(fn ($notification) => [
            'id'         => $notification->id,
            'type'       => class_basename($notification->type),
            'data'       => $notification->data,
            'read_at'    => $notification->read_at,
            'created_at' => $notification->created_at,
        ]));
    }

    public function unread(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->unreadNotifications()
            ->latest()
            ->get()
            ->map(fn ($notification) => [
                'id'         => $notification->id,
                'type'       => class_basename($notification->type),
                'data'       => $notification->data,
                'read_at'    => $notification->read_at,
                'created_at' => $notification->created_at,
            ]);

        return response()->json([
            'data'  => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
