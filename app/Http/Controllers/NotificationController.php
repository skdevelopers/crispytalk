<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Class NotificationController
 *
 * Handles sending, retrieving, and managing notifications,
 * including real-time broadcasting via rtc.crispytalk.com.
 */
class NotificationController extends Controller
{
    /**
     * Send a notification and broadcast it via WebSockets.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function send(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message'     => 'required|string',
            'postId'      => 'nullable|integer',
            'recipientId' => 'required|exists:users,id',
            'senderId'    => 'required|exists:users,id',
            'type'        => 'required|string',
            'timestamp'   => 'nullable|date',
        ]);

        $data['timestamp'] = $data['timestamp'] ?? now();

        $notification = UserNotification::create($data);

        // Broadcast notification via rtc.crispytalk.com WebSocket server
        Http::post('https://rtc.crispytalk.com/notify', [
            'event' => 'NewNotification',
            'data' => $notification
        ]);

        return response()->json(['message' => 'Notification sent successfully'], 201);
    }

    /**
     * Fetch notifications for the authenticated user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        Log::info('Authenticated user:', ['id' => $user->id]);

        $notifications = UserNotification::where('recipientId', $user->id)
            ->with('sender:id,name,email')
            ->latest('timestamp')
            ->paginate(10);

        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function markAsRead(int $id): JsonResponse
    {
        $notification = UserNotification::findOrFail($id);
        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read'], 200);
    }

    /**
     * Mark all notifications as read for the authenticated user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        UserNotification::where('recipientId', $user->id)->update(['read_at' => now()]);

        // Notify via WebSocket server
        Http::post('https://rtc.crispytalk.com/notify', [
            'event' => 'MarkAllAsRead',
            'data' => ['recipientId' => $user->id]
        ]);

        return response()->json(['message' => 'All notifications marked as read'], 200);
    }
}
