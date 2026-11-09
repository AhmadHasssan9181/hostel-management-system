<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($request->boolean('unread_only', false)) {
            $query->whereNull('read_at');
        }

        $perPage = min(max($request->integer('per_page', 20), 1), 100);

        return response()->json([
            'unread_count' => AppNotification::where('user_id', $request->user()->id)->whereNull('read_at')->count(),
            'notifications' => $query->paginate($perPage),
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return response()->json(['message' => 'Notification marked as read.', 'notification' => $notification]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $count = AppNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['message' => "{$count} notifications marked as read."]);
    }

    public function destroy(Request $request, AppNotification $notification): JsonResponse
    {
        abort_unless($notification->user_id === $request->user()->id, 403);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }
}
