<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Class Chat
 *
 * @property int $id
 * @property string|null $createdAt
 * @property string|null $lastMessage
 * @property array $users
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|Message[] $messages
 */
class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'createdAt', 'lastMessage', 'users'
    ];

    protected $casts = [
        'users' => 'array',
    ];

    /**
     * A chat has many messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id');
    }
}
