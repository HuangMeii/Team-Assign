<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ClassSection;
use App\Models\Groups;
use App\Models\Group_Members;
use App\Models\Invites;
use App\Models\Join_Requests;
use App\Models\BlockedUser;
use App\Notifications\ResetPasswordNotification;
use App\Models\PasswordHistory;
use Illuminate\Support\Facades\DB;

/**
 * App\Models\User
 *
 * @property int $user_id
 * @property string $email
 * @property string $password
 * @property string $role
 * @property string $name
 * @property bool $isFirstLogin
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
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'email',
        'password',
        'role',
        'name',
        'isFirstLogin',
        'is_active',
        'must_change_password',
        'is_deleted',
        'email_verified_at',
        'pending_email',
        'unread_message_count',
        'unread_notifications',
        'flagged_seen_at',
        'join_requests_seen_at',
        'invites_seen_at',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
        'must_change_password' => 'boolean',
        'is_active' => 'boolean',
        'isFirstLogin' => 'boolean',
        'email_verified_at' => 'datetime',
        'unread_message_count' => 'integer',
        'unread_notifications' => 'integer',
        'flagged_seen_at' => 'datetime',
        'join_requests_seen_at' => 'datetime',
        'invites_seen_at' => 'datetime',
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

    public function passwordHistories(): HasMany
    {
        return $this->hasMany(PasswordHistory::class, 'user_id', 'user_id');
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
     * Gửi email đặt lại mật khẩu (tiếng Việt) — dùng cho chức năng "Quên mật khẩu".
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Bugfix B1 [R71]: sinh vién làm trưởng nhóm KHÔNG đổi vai trò hệ tộng.
     * Trạng thái "Nhóm trưởng" derive động từ groups.leader_id.
     *
     * @property-read bool $is_leader
     */
    public function getIsLeaderAttribute(): bool
    {
        return $this->groupsLed()->exists();
    }

    /**
     * Bugfix B2 [R71]: "Đã có nhóm" tính động (trưởng nhóm hoặc thành vién)
     * przez query na group_members/groups, bez cờ duy minha.
     *
     * @property-read bool $has_group
     */
        public function getHasGroupAttribute(): bool
    {
        return $this->is_leader || Group_Members::where('user_id', $this->user_id)->exists();
    }

    /**
     * Danh sách người dùng mà user này đã chặn.
     */
    public function blockedUsers(): HasMany
    {
        return $this->hasMany(BlockedUser::class, 'blocker_id', 'user_id');
    }

    /**
     * Kiểm tra xem người dùng khác ($userId) có bị chặn bởi user này không.
     */
    public function hasBlocked(int $userId): bool
    {
        return BlockedUser::isBlocked($this->user_id, $userId);
    }

    /**
     * Tăng số tin nhắn chưa đọc.
     */
    public function incrementUnreadMessages(): void
    {
        $this->increment('unread_message_count');
    }

    /**
     * Đặt lại số tin nhắn chưa đọc về 0.
     */
    public function resetUnreadMessages(): void
    {
        $this->update(['unread_message_count' => 0]);
    }

    /**
     * Tăng số thông báo chưa đọc (badge chuông + badge "Yêu cầu" của leader).
     * Được gọi tự động từ NotificationService::create().
     */
    public function incrementUnreadNotifications(int $amount = 1): void
    {
        $this->increment('unread_notifications', $amount);
    }

    /**
     * Đặt lại số thông báo chưa đọc về 0 (sau khi đánh dấu đã đọc).
     */
    public function resetUnreadNotifications(): void
    {
        $this->update(['unread_notifications' => 0]);
    }

    /**
     * Alias để view dùng đúng quy ước `Auth::user()->unread_notifications_count`.
     */
    public function getUnreadNotificationsCountAttribute(): int
    {
        return (int) ($this->attributes['unread_notifications'] ?? 0);
    }

    /**
     * Đếm số yêu cầu tham gia nhóm (join request) chưa được xử lý của user này
     * (dành cho leader).
     */
    public function getPendingJoinRequestsCountAttribute(): int
    {
        return DB::table('join_requests')
            ->whereIn('group_id', function ($q) {
                $q->select('group_id')->from('groups')->where('leader_id', $this->user_id);
            })
            ->where('status', 'Pending')
            ->where('created_at', '>', $this->badgeSeenAt('join_requests_seen_at'))
            ->count();
    }

    /** Đếm số lời mời tham gia nhóm đang chờ CHƯA XEM (badge "Lời mời" trên sidebar). */
    public function getPendingInvitesCountAttribute(): int
    {
        return Invites::where('member_id', $this->user_id)
            ->where('status', 'Pending')
            ->where('created_at', '>', $this->badgeSeenAt('invites_seen_at'))
            ->count();
    }

    /** Đánh dấu đã xem badge "Yêu cầu": badge về 0 tới khi có yêu cầu mới. */
    public function markJoinRequestsSeen(): void
    {
        $this->forceFill(['join_requests_seen_at' => now()])->save();
    }

    /** Đánh dấu đã xem badge "Lời mời": badge về 0 tới khi có lời mời mới. */
    public function markInvitesSeen(): void
    {
        $this->forceFill(['invites_seen_at' => now()])->save();
    }

    /** Mốc "đã xem" của badge: null -> 1970 nên mọi bản ghi đang có là "mới". */
    private function badgeSeenAt(string $column): string
    {
        return optional($this->{$column})->toDateTimeString() ?? '1970-01-01 00:00:00';
    }
}