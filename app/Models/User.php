<?php

namespace App\Models;

// Import necessary Laravel components
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $bio
 * @property string|null $gender
 * @property string|null $profileUrl
 * @property string|null $phone
 * @property string|null $bgUrl
 * @property string $userStatus
 * @property string $blockStatus
 * @property bool $isOnline
 * @property string $userType
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read Collection|Post[] $posts
 * @property-read Collection|Video[] $videos
 * @property-read Collection|userNotification[] $notificationsReceived
 * @property-read Collection|userNotification[] $notificationsSent
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';
    /**
     * The data type of the primary key.
     *
     * @var string
     */
    private mixed $background_image;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    private mixed $profile_image;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'user_id',       // Foreign key for parent user (self-referencing relationship)
        'name',
        'nickName',
        'email',
        'password',
        'phone',
        'profileUrl',
        'bgUrl',
        'bio',
        'gender',
        'instagram',
        'facebook',
        'userType',
        'created_at',
        'updated_at',
        'isOnline',
        'userStatus',
        'blockStatus',
        'likes',
        'followers',
        'following',
        'savedPosts',
        'blocks',
        'profile_image',
        'background_image',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'likes'       => 'array',
        'followers'   => 'array',
        'following'   => 'array',
        'savedPosts'  => 'array',
        'blocks'      => 'array',
        'isOnline'    => 'boolean',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'email_verified_at' => 'datetime',
        'createdAt' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Accessors to get the full URL of the images

    /**
     * Get the parent user associated with the current user.
     *
     * This defines a self-referencing one-to-many relationship.
     *
     * @return string|null
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        return $this->profile_image ? asset('storage/' . $this->profile_image) : null;
    }

    /**
     * Get the parent user associated with the current user.
     *
     * This defines a self-referencing one-to-many relationship.
     *
     * @return string|null
     */
    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image ? asset('storage/' . $this->background_image) : null;
    }

    // Relationships

    /**
     * Get the parent user associated with the current user.
     *
     * This defines a self-referencing one-to-many relationship.
     *
     * @return BelongsTo<User>
     */
    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the child users for the current user.
     *
     * This defines a self-referencing one-to-many relationship.
     *
     * @return HasMany<User>
     */
    public function childUsers(): HasMany
    {
        return $this->hasMany(User::class, 'user_id');
    }

    /**
     * Get the users following the current user.
     *
     * This defines a many-to-many relationship through the 'followers' pivot table.
     *
     * @return BelongsToMany<User>
     */
    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'user_id', 'follower_id');
    }

    /**
     * Get the users that the current user is following.
     *
     * This defines a many-to-many relationship through the 'followers' pivot table.
     *
     * @return BelongsToMany<User>
     */
    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'followers', 'follower_id', 'user_id');
    }

    /**
     * Get the posts saved by the user.
     *
     * This defines a many-to-many relationship through the 'saved_posts' pivot table.
     *
     * @return BelongsToMany<Post>
     */
    public function savedPosts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'saved_posts');
    }

    /**
     * A user may have many posts.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'user_id');
    }

    /**
     * A user may have many videos.
     */
    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Notifications sent to this user.
     */
    public function notificationsReceived(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'recipientId');
    }

    /**
     * Notifications sent by this user.
     */
    public function notificationsSent(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'senderId');
    }

    /**
     * Reports made by this user.
     */
    public function reportsSent(): HasMany
    {
        return $this->hasMany(Report::class, 'reportBy');
    }

    /**
     * Reports received by this user.
     */
    public function reportsReceived(): HasMany
    {
        return $this->hasMany(Report::class, 'reportTo');
    }

    /**
     * Group chats where this user is admin.
     */
    public function groupChatsAdmin(): HasMany
    {
        return $this->hasMany(GroupChat::class, 'admin');
    }

    /**
     * Get all friends of this user with selected user details.
     *
     * @return BelongsToMany
     */
    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->select('users.id', 'users.name', 'users.email'); // Add more columns if needed
    }

    /**
     * Get the friends of this user where the friendship status is accepted.
     *
     * @return BelongsToMany
     */
    public function acceptedFriends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted');
    }

    /**
     * Get all comments made by the user.
     *
     * @return HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
