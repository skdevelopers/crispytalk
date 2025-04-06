<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Add a comment to a post.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addComment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id'   => 'required|integer|exists:posts,id',
            'content'   => 'required|string|max:2000',
        ]);

        $comment = Comment::create([
            'user_id'   => Auth::id(),
            'post_id'   => $validated['post_id'],
            'content'   => $validated['content'],
            'userName'  => Auth::user()->name,
        ]);

        return response()->json([
            'message' => 'Comment added successfully.',
            'comment' => $comment,
        ]);
    }

    /**
     * Get comments for a post.
     *
     * @param int $postId
     * @return JsonResponse
     */
    public function getComments(int $postId): JsonResponse
    {
        $comments = Comment::where('post_id', $postId)->with('user')->get();

        return response()->json($comments);
    }

    /**
     * Delete a comment.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteComment(int $id): JsonResponse
    {
        $comment = Comment::where('id', $id)->where('user_id', Auth::id())->first();

        if (!$comment) {
            return response()->json(['message' => 'Comment not found.'], 404);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
