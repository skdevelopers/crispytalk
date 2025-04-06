<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Report
 *
 * @property int $id
 * @property string|null $createdAt
 * @property int $reportBy
 * @property int|null $reportTo
 * @property string $text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read User $reporter
 * @property-read User $reportedUser
 */
class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'createdAt', 'reportBy', 'reportTo', 'text'
    ];

    /**
     * The user who reported.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportBy');
    }

    /**
     * The user who is being reported.
     */
    public function reportedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reportTo');
    }
}
