<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class userNotification
 *
 * @property int $id
 * @property string $message
 * @property int|null $postId
 * @property int $recipientId
 * @property int $senderId
 * @property string $type
 * @property Carbon $timestamp
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $sender
 * @property-read User $recipient
 */
class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';
    protected $fillable = [
        'message', 'postId', 'recipientId', 'senderId', 'type', 'timestamp', 'read_at'
    ];

    protected $dates = [
        'timestamp', 'read_at'
    ];

    /**
     * The sender of the notification.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senderId');
    }

    /**
     * The recipient of the notification.
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipientId');
    }
}
