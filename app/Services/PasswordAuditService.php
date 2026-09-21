<?php

namespace App\Services;

use App\Models\Notifications;
use App\Models\PasswordHistory;
use App\Models\User;

class PasswordAuditService
{
    public static function record(User $user, ?User $changedBy, string $source): void
    {
        PasswordHistory::create([
            'user_id' => $user->user_id,
            'changed_by' => $changedBy?->user_id,
            'source' => $source,
        ]);

        Notifications::create([
            'user_id' => $user->user_id,
            'type' => 'password_changed',
            'title' => 'Mật khẩu đã được thay đổi',
            'message' => $changedBy
                ? "Mật khẩu được thay đổi bởi {$changedBy->name} vào " . now()->format('d/m/Y H:i') . '.'
                : 'Mật khẩu được thay đổi vào ' . now()->format('d/m/Y H:i') . '.',
            'url' => route('users.profile.password'),
            'data' => json_encode(['changed_by' => $changedBy?->user_id, 'source' => $source]),
        ]);
    }
}