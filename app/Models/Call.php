<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Call
 *
 * @property int $id
 * @property int $caller_id
 * @property int $callee_id
 * @property string $call_type  // "audio" or "video"
 * @property string $status     // "pending", "accepted", "rejected", "ended"
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $caller
 * @property-read User $callee
 */
class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_id', 'callee_id', 'call_type', 'status', 'started_at', 'ended_at'
    ];

    protected array $dates = [
        'started_at', 'ended_at'
    ];

    /**
     * The user who initiated the call.
     */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'caller_id');
    }

    /**
     * The user who is being called.
     */
    public function callee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'callee_id');
    }
}
