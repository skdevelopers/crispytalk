<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Class Video
 *
 * @property int $id
 * @property string $filename
 * @property string $path
 * @property string|null $transcoded_paths
 * @property string|null $thumbnail
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Video extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'filename', 'path', 'status', 'thumbnail', 'transcoded_paths'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
