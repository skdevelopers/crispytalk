<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class MessageController
 *
 * Handles sending and retrieving chat messages.
 */
class MessageController extends Controller
{
    /**
     * Fetch messages of a chat.
     *
     * @param Chat $chat
     * @return JsonResponse
     */
    public function index(Chat $chat): JsonResponse
    {
        // Ensure the authenticated user is part of the chat
        if (!in_array(Auth::id(), $chat->users)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $messages = Message::where('chat_id', $chat->id)->orderBy('created_at', 'asc')->get();
        return response()->json($messages);
    }

    /**
     * Send a message in a chat.
     *
     * @param Request $request
     * @param Chat $chat
     * @return JsonResponse
     */
    public function store(Request $request, Chat $chat): JsonResponse
    {
        // Ensure the authenticated user is part of the chat
        if (!in_array(Auth::id(), $chat->users)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string',
            'type' => 'required|string|in:text,image,video',
        ]);

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
            'type' => $request->type,
        ]);

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }
}
