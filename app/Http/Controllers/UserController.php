<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Fetch all users with pagination.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllUsers(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $users = User::paginate($perPage);

        Log::info('Fetched users', ['page' => $request->input('page', 1)]);

        return response()->json($users);
    }

    /**
     * Get the authenticated user profile.
     *
     * @return JsonResponse
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            Log::warning('Unauthorized profile access attempt.');
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return response()->json(['user' => $user]);
    }

    /**
     * Fetch a specific user by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getUserById(int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the authenticated user profile.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'       => 'nullable|string|max:255',
            'nickName'   => 'nullable|string|max:255',
            'phone'      => 'nullable|string|max:20',
            'profileUrl' => 'nullable|url|max:2048',
            'bgUrl'      => 'nullable|url|max:2048',
            'bio'        => 'nullable|string|max:1000',
            'instagram'  => 'nullable|url|max:255',
            'facebook'   => 'nullable|url|max:255',
            'likes'      => 'nullable|array',
            'followers'  => 'nullable|array',
            'following'  => 'nullable|array',
            'savedPosts' => 'nullable|array',
            'blocks'     => 'nullable|array',
            'isOnline'   => 'nullable|boolean',
            'userStatus' => 'nullable|string|max:255',
            'blockStatus'=> 'nullable|string|max:255',
            'gender'     => 'nullable|string|max:255',
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user'    => $user->fresh(),
        ]);
    }

    /**
     * Delete the authenticated user account.
     *
     * @return JsonResponse
     */
    public function deleteAccount(): JsonResponse
    {
        $user = Auth::user();
        $user->delete();

        return response()->json(['message' => 'Account deleted successfully']);
    }

    /**
     * Get all posts (videos) uploaded by the authenticated user.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response containing paginated user posts.
     */
    public function getUserPosts(Request $request): JsonResponse
    {
        $posts = Post::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            $post->url = asset($post->path);
            $post->thumbnail_url = $post->thumbnail ? asset($post->thumbnail) : null;
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Get all posts (videos) of a specific user (profile view).
     *
     * @param int $userId The ID of the user whose posts to fetch.
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response with the user's posts.
     */
    public function getUserProfilePosts(int $userId, Request $request): JsonResponse
    {
        $posts = Post::where('user_id', $userId)
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            $post->url = asset($post->path);
            $post->thumbnail_url = $post->thumbnail ? asset($post->thumbnail) : null;
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Get posts (videos) from friends' uploads (mutual follow, audience 'friends').
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response with friends' posts.
     */
    public function friendsFeed(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $friendIds = $user->friends()->pluck('users.id');
        Log::info('Friend IDs:', $friendIds->toArray());

        $posts = Post::whereIn('user_id', $friendIds)
            ->where('audience', 'friends')
            ->latest()
            ->paginate(10);

        $posts->getCollection()->transform(function ($post) {
            $post->url = asset($post->path);
            $post->thumbnail_url = $post->thumbnail ? asset($post->thumbnail) : null;
            return $post;
        });

        return response()->json($posts);
    }

    /**
     * Handle the upload of profile and background images.
     *
     * @param Request $request The incoming request containing the images.
     * @return JsonResponse A JSON response indicating success or failure.
     */
    public function uploadImages(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'background_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid image upload.',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete the old profile image if it exists
            if ($user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            // Store the new profile image
            $profileImagePath = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $profileImagePath;
        }

        // Handle background image upload
        if ($request->hasFile('background_image')) {
            // Delete the old background image if it exists
            if ($user->background_image) {
                Storage::disk('public')->delete($user->background_image);
            }
            // Store the new background image
            $backgroundImagePath = $request->file('background_image')->store('background_images', 'public');
            $user->background_image = $backgroundImagePath;
        }

        // Save the user's updated profile
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Images uploaded successfully.',
            'data' => [
                'profile_image_url' => $user->profile_image ? asset('storage/' . $user->profile_image) : null,
                'background_image_url' => $user->background_image ? asset('storage/' . $user->background_image) : null,
            ],
        ]);
    }

}
