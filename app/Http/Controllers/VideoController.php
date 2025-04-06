<?php

namespace App\Http\Controllers;

use App\Jobs\TranscodeVideo;
use App\Models\Post;
use App\Models\Video;
use App\Services\VideoTranscoderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Class VideoController
 *
 * Handles video uploading, retrieval, updating, and deletion.
 */
class VideoController extends Controller
{
    /**
     * The video transcoder service instance.
     *
     * @var VideoTranscoderService
     */
    protected VideoTranscoderService $videoTranscoder;

    /**
     * VideoController constructor.
     *
     * @param VideoTranscoderService $videoTranscoder
     */
    public function __construct(VideoTranscoderService $videoTranscoder)
    {
        $this->videoTranscoder = $videoTranscoder;
    }

    /**
     * Upload and process a video.
     *
     * This method stores the uploaded video in the "videos/input" directory under public,
     * creates a video record with a "processing" status, and dispatches a transcoding job.
     * A thumbnail is generated and stored in "videos/thumbnails" before returning a response.
     *
     * @param Request $request The incoming request with the video file.
     * @return JsonResponse A JSON response containing video details.
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:mp4,mov,avi,wmv,mkv|max:92160', // 90MB Max
        ]);

        $file = $request->file('file');
        $videoFilename = $this->generateFilename($file);

        // Define storage paths
        $videoPath = public_path('videos/input');
        $thumbnailPath = public_path('videos/thumbnails');

        // Ensure directories exist
        if (!is_dir($videoPath)) {
            mkdir($videoPath, 0775, true);
        }
        if (!is_dir($thumbnailPath)) {
            mkdir($thumbnailPath, 0775, true);
        }

        // Move video file
        $file->move($videoPath, $videoFilename);
        $relativeVideoPath = 'videos/input/' . $videoFilename;
        Log::info('Video stored', ['path' => $relativeVideoPath]);

        // Generate thumbnail immediately
        $thumbnailFilename = pathinfo($videoFilename, PATHINFO_FILENAME) . '.jpg';
        $thumbnailFullPath = $thumbnailPath . '/' . $thumbnailFilename;
        $relativeThumbnailPath = 'videos/thumbnails/' . $thumbnailFilename;

        if ($this->generateThumbnail($videoPath . '/' . $videoFilename, $thumbnailFullPath)) {
            Log::info('Thumbnail generated', ['thumbnail' => $relativeThumbnailPath]);
        } else {
            Log::error('Thumbnail generation failed', ['video' => $relativeVideoPath]);
        }

        // Store video record in database
        $video = Post::create([
            'user_id'   => auth()->id(),
            'filename'  => $videoFilename,
            'path'      => $relativeVideoPath,
            'status'    => 'processing',
            'thumbnail' => $relativeThumbnailPath, // Ensuring thumbnail is not null
        ]);

        // Dispatch transcoding job in the background
        TranscodeVideo::dispatch($video->path, $video->id)->onQueue('videos');

        // Optional: notify via RTC server
        Http::post('https://rtc.crispytalk.info/notify', [
            'event' => 'NewVideoUploaded',
            'data'  => [
                'video_id' => $video->id,
                'url'      => asset($video->path),
                'thumbnail' => asset($video->thumbnail),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Video uploaded successfully. Transcoding in progress.',
            'video'   => [
                'id'        => $video->id,
                'filename'  => $video->filename,
                'url'       => asset($video->path),
                'thumbnail' => asset($video->thumbnail), // Always return a valid thumbnail
            ],
        ]);
    }

    /**
     * Generate a thumbnail from the video.
     *
     * This method extracts a frame from the middle of the video and saves it as a JPEG image.
     *
     * @param string $videoPath The full path of the video file.
     * @param string $thumbnailPath The full path to save the generated thumbnail.
     * @return bool Returns true if thumbnail was created successfully, otherwise false.
     */
    private function generateThumbnail(string $videoPath, string $thumbnailPath): bool
    {
        $ffmpeg = env('FFMPEG_PATH', '/usr/bin/ffmpeg');
        $command = "$ffmpeg -i \"$videoPath\" -ss 00:00:01 -vframes 1 \"$thumbnailPath\"";

        exec($command, $output, $status);

        return $status === 0 && file_exists($thumbnailPath);
    }


    /**
     * Retrieve a specific video by model binding.
     *
     * Increments the video's view count and returns video details with public URLs.
     *
     * @param Video $video The video model automatically resolved.
     * @return JsonResponse
     */
    public function getVideo(Video $video): JsonResponse
    {
        $video->increment('views');

        return response()->json([
            'id'        => $video->id,
            'filename'  => $video->filename,
            'url'       => asset($video->path),
            'thumbnail' => $video->thumbnail ? asset($video->thumbnail) : null,
            'views'     => $video->views,
            'status'    => $video->status,
        ]);
    }

    /**
     * Fetch video paths for different resolutions.
     *
     * This method retrieves a video by its ID and returns the paths of the transcoded
     * versions at multiple resolutions, including the original video.
     *
     * @param int $videoId The ID of the video to fetch.
     * @return JsonResponse A JSON response containing the video paths or an error message.
     */
    public function getVideoPaths(int $videoId): JsonResponse
    {
        $video = Video::find($videoId);

        if (!$video) {
            return response()->json([
                'success' => false,
                'message' => 'Video not found.',
            ], 404);
        }

        // Define resolutions with their respective paths
        $resolutions = [
            ['width' => 1920, 'height' => 1080, 'bitrate' => 3000],
            ['width' => 1280, 'height' => 720,  'bitrate' => 1500],
            ['width' => 640,  'height' => 360,  'bitrate' => 500],
            ['width' => 320,  'height' => 180,  'bitrate' => 300],
        ];

        $basePath = dirname($video->path); // Extract base directory
        $filenameWithoutExt = pathinfo($video->filename, PATHINFO_FILENAME);

        // Construct resolution paths
        $videoPaths = [];
        foreach ($resolutions as $res) {
            $resolutionPath = "$basePath/{$filenameWithoutExt}_{$res['width']}x{$res['height']}.mp4";
            $videoPaths[] = [
                'resolution' => "{$res['width']}x{$res['height']}",
                'bitrate' => $res['bitrate'],
                'path' => asset($resolutionPath), // Ensure it's a valid URL
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Video paths retrieved successfully.',
            'video' => [
                'id' => $video->id,
                'filename' => $video->filename,
                'original' => asset($video->path),
                'thumbnail' => asset($video->thumbnail),
                'resolutions' => $videoPaths,
            ],
        ]);
    }


    /**
     * Get all videos uploaded by the authenticated user (paginated).
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response containing paginated video records.
     */
    public function getUserVideos(Request $request): JsonResponse
    {
        $videos = Video::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        $videos->getCollection()->transform(function ($video) {
            $video->url = asset($video->path);
            $video->thumbnail_url = $video->thumbnail ? asset($video->thumbnail) : null;
            return $video;
        });

        return response()->json($videos);
    }

    /**
     * Get all videos of a specific user (profile view) with pagination.
     *
     * @param int $userId The ID of the user whose videos to fetch.
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response with the user's videos.
     */
    public function getUserProfileVideos(int $userId, Request $request): JsonResponse
    {
        $videos = Video::where('user_id', $userId)
            ->latest()
            ->paginate(10);

        $videos->getCollection()->transform(function ($video) {
            $video->url = asset($video->path);
            $video->thumbnail_url = $video->thumbnail ? asset($video->thumbnail) : null;
            return $video;
        });

        return response()->json($videos);
    }

    /**
     * Get the home feed videos (all public videos) with pagination.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response with public videos.
     */
    public function homeFeed(Request $request): JsonResponse
    {
        $videos = Video::where('audience', 'public')
            ->latest()
            ->paginate(10);

        $videos->getCollection()->transform(function ($video) {
            $video->url = asset($video->path);
            $video->thumbnail_url = $video->thumbnail ? asset($video->thumbnail) : null;
            return $video;
        });

        return response()->json($videos);
    }

    /**
     * Get videos from friends' uploads (mutual follow, audience 'friends') with pagination.
     *
     * @param Request $request The incoming request.
     * @return JsonResponse A JSON response with friends' videos.
     */
    public function friendsFeed(Request $request): JsonResponse
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $friendIds = $user->friends()->pluck('id');
        Log::info('Friend IDs:', $friendIds->toArray());

        $videos = Video::whereIn('user_id', $friendIds)
            ->where('audience', 'friends')
            ->latest()
            ->paginate(10);

        $videos->getCollection()->transform(function ($video) {
            $video->url = asset($video->path);
            $video->thumbnail_url = $video->thumbnail ? asset($video->thumbnail) : null;
            return $video;
        });

        return response()->json($videos);
    }

    /**
     * Update video details (such as audience, likes, and views).
     *
     * @param Request $request The incoming request containing updated video data.
     * @param int $id The ID of the video to update.
     * @return JsonResponse A JSON response with the updated video details.
     */
    public function updateVideo(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'audience' => 'nullable|in:public,private,friends',
            'likes'    => 'nullable|integer',
            'views'    => 'nullable|integer',
        ]);

        $video = Video::where('user_id', auth()->id())->findOrFail($id);
        $video->update($request->only(['audience', 'likes', 'views']));

        return response()->json([
            'success' => true,
            'message' => 'Video updated successfully.',
            'video'   => [
                'id'            => $video->id,
                'filename'      => $video->filename,
                'url'           => asset($video->path),
                'thumbnail_url' => $video->thumbnail ? asset($video->thumbnail) : null,
                'audience'      => $video->audience,
                'likes'         => $video->likes,
                'views'         => $video->views,
            ],
        ]);
    }

    /**
     * Delete a video uploaded by the authenticated user.
     *
     * Deletes both the video file (and thumbnail, if present) from the public disk and removes the DB record.
     *
     * @param int $id The ID of the video to delete.
     * @return JsonResponse A JSON response confirming deletion.
     */
    public function deleteVideo(int $id): JsonResponse
    {
        $video = Video::where('user_id', auth()->id())->findOrFail($id);

        // Delete video file
        if (file_exists(public_path($video->path))) {
            unlink(public_path($video->path));
        }
        // Delete thumbnail if exists
        if ($video->thumbnail && file_exists(public_path($video->thumbnail))) {
            unlink(public_path($video->thumbnail));
        }

        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Video deleted successfully.'
        ]);
    }


}
