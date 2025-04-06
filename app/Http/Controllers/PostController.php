<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Jobs\TranscodeVideo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class PostController
 *
 * Handles CRUD operations for posts, including media uploads, video transcoding, and streaming.
 */
class PostController extends Controller
{
    /**
     * Retrieve paginated posts.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $posts = Post::with(['user:id,name', 'comments'])
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json($posts);
    }

    /**
     * Store a new post with optional media upload.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'timeStamp'   => 'nullable|string',
            'title'       => 'required|string',
            'audience'    => 'required|string',
            'filterIndex' => 'required|integer',
            'media'       => 'nullable|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:512000', // 500MB
            'thumbnail'   => 'nullable|file|mimes:jpg,jpeg,png|max:10240', // 10MB
            'likes'       => 'nullable|array',
            'saved'       => 'nullable|array',
            'views'       => 'nullable|integer',
        ]);

        $data['user_id'] = auth()->id();
        $data['status'] = 'processing';
        $data['thumbnail'] = null;

        if ($request->hasFile('media')) {
            $data = $this->handleMediaUpload($request, $data);
        }

        if ($request->hasFile('thumbnail')) {
            $data['thumbnail'] = $this->storeFile($request->file('thumbnail'), 'videos/thumbnails');
        }

        $post = Post::create($data);

        if ($data['is_video']) {
            dispatch(new TranscodeVideo($post))->onQueue('videos');
        } else {
            $post->update(['status' => 'completed']);
        }

        return $this->postResponse($post, 'Post created successfully.');
    }

    /**
     * Retrieve a single post.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        $post = Post::with(['user:id,name', 'comments'])->findOrFail($id);
        return response()->json($post);
    }

    /**
     * Update a post.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $data = $request->validate([
            'title'       => 'required|string',
            'audience'    => 'required|string',
            'filterIndex' => 'required|integer',
        ]);

        $post->update($data);
        return response()->json(['message' => 'Post updated successfully', 'post' => $post]);
    }

    /**
     * Delete a post along with its media.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $post = Post::findOrFail($id);
        $this->deleteFile($post->path);
        $this->deleteFile($post->thumbnail);

        $post->delete();
        return response()->json(['message' => 'Post deleted successfully']);
    }

    /**
     * Stream a video file.
     *
     * @param Post $post
     * @return StreamedResponse
     */
    public function showVideo(Post $post): StreamedResponse
    {
        $videoPath = public_path($post->path);
        return $this->streamFile($videoPath);
    }

    /**
     * Get public posts for the home feed.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function homeFeed(Request $request): JsonResponse
    {
        $posts = Post::where('audience', 'public')->latest()->paginate(10);
        return response()->json($posts);
    }

    /**
     * Update post status.
     *
     * @param Request $request
     * @param Post $post
     * @return JsonResponse
     */
    public function updateStatus(Request $request, Post $post): JsonResponse
    {
        $request->validate(['status' => 'required|string|in:processing,completed,failed']);
        $post->update(['status' => $request->status]);

        return response()->json(['message' => 'Post status updated successfully', 'post' => $post]);
    }

    // ============================
    //        HELPER METHODS
    // ============================

    /**
     * Handles media file upload.
     *
     * @param Request $request
     * @param array $data
     * @return array
     */
    private function handleMediaUpload(Request $request, array $data): array
    {
        $file = $request->file('media');
        $ext = $file->getClientOriginalExtension();
        $isVideo = in_array($ext, ['mp4', 'mov', 'avi']);

        $data['filename'] = uniqid('media_') . ".$ext";
        $data['path'] = $this->storeFile($file, $isVideo ? 'videos/input' : 'images');
        $data['is_video'] = $isVideo;

        if ($isVideo) {
            $data['thumbnail'] = $this->generateThumbnail(public_path($data['path']));
        }

        return $data;
    }

    /**
     * Stores a file and returns its storage path.
     *
     * @param mixed $file
     * @param string $folder
     * @return string
     */
    private function storeFile($file, string $folder): string
    {
        return "storage/" . $file->store($folder, 'public');
    }

    /**
     * Deletes a file if it exists.
     *
     * @param string|null $filePath
     */
    private function deleteFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }

    /**
     * Generates a video thumbnail.
     *
     * @param string $videoPath
     * @return string|null
     */
    private function generateThumbnail(string $videoPath): ?string
    {
        $thumbnailPath = pathinfo($videoPath, PATHINFO_DIRNAME) . '/thumbnails/' . pathinfo($videoPath, PATHINFO_FILENAME) . '.jpg';
        $command = env('FFMPEG_PATH', '/usr/bin/ffmpeg') . " -i \"$videoPath\" -ss 00:00:01 -vframes 1 \"$thumbnailPath\" 2>&1";
        exec($command, $output, $status);

        return $status === 0 ? str_replace(public_path(), 'storage', $thumbnailPath) : null;
    }

    /**
     * Streams a file efficiently.
     *
     * @param string $filePath
     * @return StreamedResponse
     */
    private function streamFile(string $filePath): StreamedResponse
    {
        return response()->stream(fn () => readfile($filePath), 200, [
            'Content-Type'  => 'video/mp4',
            'Accept-Ranges' => 'bytes',
        ]);
    }
}
