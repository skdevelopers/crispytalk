<?php

namespace App\Services;

use App\Models\Post;
use FFMpeg\Coordinate\Dimension;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Video\X264;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Class VideoTranscoderService
 *
 * Handles transcoding of videos into multiple resolutions and generates thumbnails.
 *
 * @package App\Services
 */
class VideoTranscoderService
{
    /**
     * Directory for transcoded videos.
     *
     * @var string
     */
    private string $outputDirectory;

    /**
     * Directory for generated thumbnails.
     *
     * @var string
     */
    private string $thumbnailDirectory;

    /**
     * Path to the ffmpeg binary.
     *
     * @var string
     */
    private string $ffmpegPath = '/usr/bin/ffmpeg';

    /**
     * Path to the ffprobe binary.
     *
     * @var string
     */
    private string $ffprobePath = '/usr/bin/ffprobe';

    /**
     * VideoTranscoderService constructor.
     */
    public function __construct()
    {
        $this->outputDirectory = storage_path('app/public/videos/output/');
        $this->thumbnailDirectory = storage_path('app/public/videos/thumbnails/');

        $this->createDirectoryIfNeeded($this->outputDirectory);
        $this->createDirectoryIfNeeded($this->thumbnailDirectory);
    }

    /**
     * Transcode a video into multiple resolutions and generate a thumbnail.
     *
     * @param string $filePath The path to the input video file.
     * @param int $postId The ID of the post record.
     * @return array An array containing success status, transcoded video URLs, and thumbnail URL.
     */
    public function transcodeVideo(string $filePath, int $postId): array
    {
        $post = Post::find($postId);
        if (!$post) {
            return ['success' => false, 'error' => 'Post not found.'];
        }

        try {
            $outputDir = $this->outputDirectory . pathinfo($post->filename, PATHINFO_FILENAME);
            $this->createDirectoryIfNeeded($outputDir);

            $resolutions = [
                ['width' => 1920, 'height' => 1080, 'bitrate' => 3000],
                ['width' => 1280, 'height' => 720,  'bitrate' => 1500],
                ['width' => 640,  'height' => 360,  'bitrate' => 500],
                ['width' => 320,  'height' => 180,  'bitrate' => 300],
            ];

            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries'  => $this->ffmpegPath,
                'ffprobe.binaries' => $this->ffprobePath,
            ]);

            $ffmpegVideo = $ffmpeg->open($filePath);
            $transcodedVideos = [];

            foreach ($resolutions as $res) {
                $outputFilename = "video_{$res['width']}x{$res['height']}.mp4";
                $outputPath = $outputDir . '/' . $outputFilename;

                $format = new X264();
                $format->setKiloBitrate($res['bitrate'])
                    ->setAudioCodec('aac')
                    ->setVideoCodec('libx264')
                    ->setAdditionalParameters(['-movflags', '+faststart']);

                $ffmpegVideo->filters()
                    ->resize(new Dimension($res['width'], $res['height']))
                    ->synchronize();
                $ffmpegVideo->save($format, $outputPath);

                $transcodedVideos["{$res['width']}x{$res['height']}"] = asset('storage/videos/output/' . pathinfo($post->filename, PATHINFO_FILENAME) . '/' . $outputFilename);
            }

            $thumbnail = $this->generateThumbnail($filePath);

            $post->update([
                'transcoded_paths' => json_encode($transcodedVideos),
                'thumbnail' => $thumbnail,
                'status' => 'completed',
            ]);

            return [
                'success' => true,
                'videoUrls' => $transcodedVideos,
                'thumbnail' => $thumbnail,
            ];
        } catch (\Exception $e) {
            Log::error("Video transcoding failed: " . $e->getMessage());

            $post->update(['status' => 'failed']);

            return ['success' => false, 'error' => 'Transcoding failed.'];
        }
    }

    /**
     * Generate a thumbnail image for a video.
     *
     * @param string $filePath The path to the video file.
     * @return string|null The public URL of the generated thumbnail, or null if generation fails.
     */
    public function generateThumbnail(string $filePath): ?string
    {
        $ffmpeg = FFMpeg::create([
            'ffmpeg.binaries'  => $this->ffmpegPath,
            'ffprobe.binaries' => $this->ffprobePath,
        ]);

        try {
            $ffmpegVideo = $ffmpeg->open($filePath);
            $thumbnailFilename = Str::uuid() . '.jpg';
            $thumbnailPath = $this->thumbnailDirectory . $thumbnailFilename;

            foreach ([1, 5, 10] as $seconds) {
                $ffmpegVideo->frame(TimeCode::fromSeconds($seconds))->save($thumbnailPath);
                if (file_exists($thumbnailPath) && filesize($thumbnailPath) > 0) {
                    break;
                }
            }

            return file_exists($thumbnailPath) ? asset('storage/videos/thumbnails/' . $thumbnailFilename) : null;
        } catch (\Exception $e) {
            Log::error('Thumbnail generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create a directory if it does not already exist.
     *
     * @param string $directory The directory path.
     * @return void
     */
    private function createDirectoryIfNeeded(string $directory): void
    {
        if (!File::exists($directory)) {
            try {
                File::makeDirectory($directory, 0755, true);
            } catch (\Exception $e) {
                Log::error('Directory creation failed: ' . $e->getMessage());
            }
        }
    }
}
