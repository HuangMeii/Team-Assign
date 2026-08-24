<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\ClassSection;
use App\Models\Groups;
use App\Models\Invites;
use App\Models\Join_Requests;

/**
 * App\Models\User
 *
 * @property int $user_id
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $name
 * @property bool $isFirstLogin
 * @property bool $isHaveGroup
 * @method BelongsToMany classes()
 * @method HasMany groupsLed()
 * @method BelongsToMany groupsJoined()
 * @method HasMany invites()
 * @method HasMany joinRequests()
 * @method HasMany sentInvites()
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ClassSection> $classes
 * @property-read int|null $classes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Groups> $groupsJoined
 * @property-read int|null $groups_joined_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Groups> $groupsLed
 * @property-read int|null $groups_led_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invites> $invites
 * @property-read int|null $invites_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Join_Requests> $joinRequests
 * @property-read int|null $join_requests_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invites> $sentInvites
 * @property-read int|null $sent_invites_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsFirstLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsHaveGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUserId($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'password',
        'role',
        'name',
        'isFirstLogin',
        'isHaveGroup',
        'is_have_group',
        'is_active',
    ];


    protected $hidden = [
        'password',
    ];

    protected $primaryKey = 'user_id';

    
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            ClassSection::class,
            'user_classes',      
            'user_id',           
            'class_id'       
        );
    }

    /**
     * Nhóm mà user lãnh đạo
     * 
     * @return HasMany
     */
    public function groupsLed(): HasMany
    {
        return $this->hasMany(Groups::class, 'leader_id', 'user_id');
    }

    /**
     * Nhóm mà user tham gia
     * 
     * @return BelongsToMany
     */
    public function groupsJoined(): BelongsToMany
    {
        return $this->belongsToMany(
            Groups::class,
            'group_members',
            'user_id',
            'group_id'
        );
    }

    /**
     * Lời mời nhận được
     * 
     * @return HasMany
     */
    public function invites(): HasMany
    {
        return $this->hasMany(Invites::class, 'member_id', 'user_id');
    }

    /**
     * Yêu cầu tham gia gửi
     * 
     * @return HasMany
     */
    public function joinRequests(): HasMany
    {
        return $this->hasMany(Join_Requests::class, 'member_id', 'user_id');
    }

    /**
     * Lời mời mà user gửi
     * 
     * @return HasMany
     */
    public function sentInvites(): HasMany
    {
        return $this->hasMany(Invites::class, 'invitedBy', 'user_id');
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!str_starts_with($model->password, '$2y$')) {
                $model->password = bcrypt($model->password);
            }
        });

        // Quan trọng: hash mật khẩu khi CẬP NHẬT (ví dụ chức năng đổi mật khẩu).
        // Nếu thiếu hook này, mật khẩu mới sẽ bị lưu dạng plaintext.
        static::updating(function ($model) {
            if (!str_starts_with($model->password, '$2y$')
                && !str_starts_with($model->password, '$argon2')) {
                $model->password = bcrypt($model->password);
            }
        });
    }

    public function hasRole($roles)
    {
        if (is_string($roles)) {
            return $this->role === $roles;
        }
        return in_array($this->role, $roles);
    }

    /**
     * Cột thật trong DB là isHaveGroup (camelCase).
     * Accessor/Mutator này cho phép cả hai cách gọi
     * $user->is_have_group và $user->isHaveGroup đều hoạt động.
     */
    public function getIsHaveGroupAttribute(): bool
    {
        return (bool) ($this->attributes['isHaveGroup'] ?? false);
    }

    public function setIsHaveGroupAttribute($value): void
    {
        $this->attributes['isHaveGroup'] = $value;
    }
}