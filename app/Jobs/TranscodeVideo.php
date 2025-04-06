<?php

namespace App\Jobs;

use App\Events\PostStatusUpdated;
use App\Models\Post;
use FFMpeg\FFMpeg;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Format\Video\X264;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class TranscodeVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1200; // Increase timeout to 20 minutes

    /**
     * The Post model instance.
     *
     * @var Post
     */
    protected Post $post;

    /**
     * Absolute path of the video file.
     *
     * @var string
     */
    protected string $videoPath;

    /**
     * Directory for storing transcode video files.
     *
     * @var string
     */
    protected string $outputDir;

    /**
     * Directory for storing generated thumbnails.
     *
     * @var string
     */
    protected string $thumbnailDir;

    /**
     * Resolutions and filenames for transcoding.
     *
     * @var array
     */
    protected array $resolutions = [
        '360p'  => ['640x360', '360p.mp4'],
        '480p'  => ['854x480', '480p.mp4'],
        '720p'  => ['1280x720', '720p.mp4'],
        '1080p' => ['1920x1080', '1080p.mp4'],
    ];

    /**
     * Create a new job instance.
     *
     * @param Post $post The post model instance containing the video file details.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
        $this->videoPath = public_path($post->path);
        $this->outputDir = public_path("videos/output/{$post->id}/");
        $this->thumbnailDir = public_path('videos/thumbnails/');
    }

    /**
     * Wake up method to reinitialize non-serializable properties.
     *
     * This method is called upon un-serialization of the job.
     *
     * @return void
     */
    public function __wakeup(): void
    {
        $this->thumbnailDir = public_path('videos/thumbnails/');
    }

    /**
     * Execute the job.
     *
     * Transcode the video into multiple resolutions, generates a thumbnail,
     * updates the post record, and fires an event to notify about the status change.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $this->prepareDirectories();
            $this->updateStatus('processing');

            $ffmpeg = $this->initializeFfmpeg();
            $ffmpegVideo = $ffmpeg->open($this->videoPath);

            $transcodedVideos = $this->transcodeVideo($ffmpegVideo);
            $thumbnailPath = $this->generateThumbnail($ffmpegVideo);

            $this->post->update([
                'status' => 'completed',
                'transcoded_paths' => json_encode($transcodedVideos),
                'thumbnail' => str_replace(public_path(), '', $thumbnailPath),
            ]);

            event(new PostStatusUpdated($this->post));
            Log::info("Transcoding completed for Post ID: {$this->post->id}");
        } catch (\Exception $e) {
            Log::error("Error in transcoding Post ID {$this->post->id}: " . $e->getMessage());
            $this->updateStatus('failed');
        }
    }

    /**
     * Initialize the FFMpeg instance.
     *
     * @return FFMpeg
     */
    protected function initializeFfmpeg()
    {
        return FFMpeg::create([
            'ffmpeg.binaries'  => env('FFMPEG_BINARIES', '/usr/bin/ffmpeg'),
            'ffprobe.binaries' => env('FFPROBE_BINARIES', '/usr/bin/ffprobe'),
            'timeout'          => 3600,
            'threads'          => 4,
        ]);
    }

    /**
     * Transcode the video into multiple resolutions.
     *
     * @param mixed $ffmpegVideo The FFMpeg video instance.
     * @return array An associative array of resolution labels and file paths.
     */
    protected function transcodeVideo(mixed $ffmpegVideo): array
    {
        $transcodedVideos = [];
        $format = new X264();
        $format->setAudioCodec('aac')
            ->setVideoCodec('libx264')
            ->setAdditionalParameters(['-movflags', '+faststart']);

        foreach ($this->resolutions as $label => [$size, $filename]) {
            $outputFile = "{$this->outputDir}{$filename}";
            [$width, $height] = explode('x', $size);

            $ffmpegVideo->filters()
                ->resize(new Dimension((int)$width, (int)$height))
                ->synchronize();
            $ffmpegVideo->save($format, $outputFile);

            $transcodedVideos[$label] = str_replace(public_path(), '', $outputFile);
            Log::info("Transcode {$label} video saved: " . $outputFile);
        }

        return $transcodedVideos;
    }

    /**
     * Generate a thumbnail image from the video.
     *
     * This method extracts a frame at 5 seconds into the video and saves it as a JPEG image.
     *
     * @param mixed $ffmpegVideo The FFMpeg video instance.
     * @return string The absolute path to the generated thumbnail.
     */
    protected function generateThumbnail(mixed $ffmpegVideo): string
    {
        $thumbnailFile = "{$this->thumbnailDir}{$this->post->id}.jpg";
        $ffmpegVideo->frame(TimeCode::fromSeconds(5))->save($thumbnailFile);

        Log::info("Thumbnail generated: " . $thumbnailFile);
        return $thumbnailFile;
    }

    /**
     * Prepare the necessary directories for output and thumbnails.
     *
     * @return void
     */
    protected function prepareDirectories(): void
    {
        $this->ensureDirectoryExists($this->outputDir);
        $this->ensureDirectoryExists($this->thumbnailDir);
    }

    /**
     * Ensure that a directory exists; if not, create it.
     *
     * @param string $path The directory path.
     * @return void
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Update the status of the post and fire an event.
     *
     * @param string $status The new status ('processing', 'completed', or 'failed').
     * @return void
     */
    protected function updateStatus(string $status): void
    {
        $this->post->update(['status' => $status]);
        event(new PostStatusUpdated($this->post));
        Log::info("Post ID: {$this->post->id} status updated to: {$status}");
    }
}
