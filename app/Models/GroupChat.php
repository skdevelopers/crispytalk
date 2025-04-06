<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class GroupChat
 *
 * @property int $id
 * @property int $admin
 * @property string|null $createdAt
 * @property string|null $groupImage
 * @property string $groupName
 * @property array $members
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $administrator
 */
class GroupChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin', 'createdAt', 'groupImage', 'groupName', 'members'
    ];

    protected $casts = [
        'members' => 'array',
    ];

    /**
     * The admin (owner) of the group.
     */
    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin');
    }
}
