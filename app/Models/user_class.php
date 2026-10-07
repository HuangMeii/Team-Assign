<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Bang noi User <-> Lop hoc phan (user_classes).
 *
 * L05: cot `status` phan biet "Dang hoc" / "Da roi lop" cho tung (user_id, class_id).
 * "Xoa khoi lop" = UPDATE status='left' + left_at=now(), dong pivot van ton tai
 * (Admin/GV van xem duoc dong xam + badge, sinh vien da roi bi an het).
 *
 * @property int $id
 * @property int $user_id
 * @property int $class_id
 * @property string $status  'studying' | 'left'
 * @property \Illuminate\Support\Carbon|null $left_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static Builder<static> studying()
 * @method static Builder<static> left()
 * @mixin \Eloquent
 */
class user_class extends Model
{
    /** @use HasFactory<\Database\Factories\UserClassFactory> */
    use HasFactory;

    /** Trang thai mac dinh: dang hoc. */
    public const STATUS_STUDYING = 'studying';

    /** Trang thai da roi lop (xoa mem). */
    public const STATUS_LEFT = 'left';

    protected $table = 'user_classes';

    protected $fillable = [
        'user_id',
        'class_id',
        'status',
        'left_at',
    ];

    protected $casts = [
        'left_at' => 'datetime',
    ];

    /** Chi cac dong dang hoc. */
    public function scopeStudying(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_STUDYING);
    }

    /** Chi cac dong da roi lop. */
    public function scopeLeft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_LEFT);
    }
}
