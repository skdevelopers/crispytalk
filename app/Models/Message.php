<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Message
 *
 * @property int $id
 * @property string|null $createdAt
 * @property string $message
 * @property int $senderId
 * @property string $type
 * @property int $chat_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Chat $chat
 * @property-read User $sender
 */
class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'createdAt', 'message', 'sender_id', 'type', 'chat_id', 'media_url'
    ];

    /**
     * Message belongs to a chat.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    /**
     * Message sender.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'senderId');
    }
}
