<?php

namespace App\Listeners;

use App\Events\PostStatusUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotifyRtcOnPostStatusUpdate
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostStatusUpdated $event): void
    {
        try {
            $post = $event->post;

            Http::post('https://rtc.crispytalk.info/notify', [
                'event' => 'PostStatusUpdated',
                'data'  => [
                    'post_id' => $post->id,
                    'status' => $post->status,
                    'video_url' => asset('storage/' . $post->path),
                    'thumbnail' => $post->thumbnail ? asset('storage/' . $post->thumbnail) : null,
                ],
            ]);

            Log::info("RTC notification sent for Post ID: {$post->id}, Status: {$post->status}");
        } catch (\Exception $e) {
            Log::error("RTC notification failed: " . $e->getMessage());
        }
    }
}
