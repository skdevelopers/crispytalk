<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Fetch all chats for the authenticated user.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $chats = Chat::whereJsonContains('users', Auth::id())->get();

        return response()->json($chats);
    }

    /**
     * Create a new chat.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'users'    => 'required|array|min:2',
            'users.*'  => 'exists:users,id',
        ]);

        // Ensure the authenticated user is included in the chat
        $users = array_unique(array_merge($request->users, [Auth::id()]));

        // Check if chat already exists
        $existingChat = Chat::whereJsonContains('users', $users)->first();
        if ($existingChat) {
            return response()->json(['message' => 'Chat already exists', 'chat' => $existingChat], 200);
        }

        $chat = Chat::create(['users' => $users]);

        return response()->json($chat, 201);
    }

    /**
     * Fetch a specific chat.
     *
     * @param Chat $chat
     * @return JsonResponse
     */
    public function show(Chat $chat): JsonResponse
    {
        // Ensure the authenticated user is part of the chat
        if (!in_array(Auth::id(), $chat->users)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($chat);
    }

    /**
     * Delete a chat if the user is a participant.
     *
     * @param Chat $chat
     * @return JsonResponse
     */
    public function deleteChat(Chat $chat): JsonResponse
    {
        // Ensure the authenticated user is part of the chat
        if (!in_array(Auth::id(), $chat->users)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $chat->delete();

        return response()->json(['message' => 'Chat deleted successfully']);
    }

    /**
     * Upload media file for chat.
     *
     * @param Request $request The request containing the file and chat ID.
     * @return JsonResponse JSON response with file URL and message data.
     */
    public function uploadMedia(Request $request): JsonResponse
    {
        $request->validate([
            'chat_id'   => 'required|exists:chats,id',
            'file'      => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mp3,wav|max:20480',
        ]);

        $chat = Chat::findOrFail($request->chat_id);
        $users = $chat->users;

        $users = array_map('intval', $users); // Convert all to integers
        $userId = intval(Auth::id()); // Ensure Auth ID is integer

        if (!in_array($userId, $users, true)) { // Strict check
            return response()->json(['error' => 'Unauthorized'], 403);
        }


        $file = $request->file('file');
        $filename = $this->generateFilename($file);
        $path = $file->storeAs('chat/media', $filename, 'public');

        // Save the media message
        $message = Message::create([
            'chat_id'   => $chat->id,
            'sender_id' => Auth::id(), // Ensure this is included
            'message'   => 'media uploading',
            'media_url' => $path,
        ]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully',
            'media_url' => asset("storage/{$path}"),
            'message_data' => $message,
        ]);
    }

    /**
     * Generate a unique filename for an uploaded file.
     *
     * @param mixed $file The uploaded file instance.
     * @return string The generated filename.
     */
    private function generateFilename(mixed $file): string
    {
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
            . '_' . now()->format('Ymd_His')
            . '_' . Str::random(6)
            . '.' . $file->getClientOriginalExtension();
    }

}
