<?php

namespace App\Events;

use App\Models\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Post $post;

    /**
     * Create a new event instance.
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('post.status.' . $this->post->id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'PostStatusUpdated';
    }

    /**
     * The data that should be sent with the broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->post->id,
            'status' => $this->post->status,
            'video_url' => asset('storage/' . $this->post->path),
            'thumbnail' => $this->post->thumbnail ? asset('storage/' . $this->post->thumbnail) : null,
        ];
    }
}

