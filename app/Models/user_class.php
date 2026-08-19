<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $class_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereClassId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|user_class whereUserId($value)
 * @mixin \Eloquent
 */
class user_class extends Model
{
    /** @use HasFactory<\Database\Factories\UserClassFactory> */
    use HasFactory;
}
