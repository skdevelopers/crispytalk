<?php

namespace App\Models;

use App\Events\PostStatusUpdated;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Post
 *
 * @property int $id
 * @property string|null $timeStamp
 * @property string $title
 * @property string $audience
 * @property int $filterIndex
 * @property string $mediaUrl
 * @property string $mediaType
 * @property string|null $thumbnail
 * @property array|null $likes
 * @property array|null $saved
 * @property int $views
 * @property int $user_id
 * @property string|null $filename
 * @property string|null $path
 * @property array|null $transcoded_paths
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $user
 * @property-read Collection|Comment[] $comments
 */
class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'timeStamp', 'title', 'audience', 'filterIndex', 'mediaUrl', 'mediaType',
        'thumbnail', 'likes', 'saved', 'views', 'user_id', 'filename', 'path',
        'transcoded_paths', 'status', 'is_video' // ✅ Add is_video here
    ];

    protected $casts = [
        'likes' => 'array',
        'saved' => 'array',
        'transcoded_paths' => 'array',
    ];

    /**
     * Post PostStatusUpdated.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::updated(function ($post) {
            if ($post->isDirty('status')) {
                event(new PostStatusUpdated($post));
            }
        });
    }

    /**
     * Post belongs to a user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all comments for the post.
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
