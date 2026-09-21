<?php


use App\Models\Groups;
use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('App.Models.User. {id}', function ($user, $id) {
    return (int) $user->user_id === (int) $id;
});

// Kênh private cá nhân – dùng cho tin nhắn 1:1, thông báo join request, badge real-time
Broadcast::channel('chat.{userId}', function ($user, $userId) {
    return (int) $user->user_id === (int) $userId;
});

Broadcast::channel('chat.group.{groupId}', function ($user, $groupId) {
    $group = Groups::find($groupId);

    if (!$group) {
        return false;
    }

    // Admin giám sát: xem được mọi khung chat nhóm nên cũng phải subscribe được
    // kênh nhóm để nhận tin nhắn real-time của thành viên (nếu không,
    // /broadcasting/auth trả 403 và trang giám sát chỉ thấy tin sau khi reload).
    if (($user->role ?? null) === 'admin') {
        return true;
    }

    // Kiểm tra user là leader hoặc là thành viên của nhóm
    $isLeader = $group->leader_id === $user->user_id;
    // Sử dụng quan hệ members() đã định nghĩa trong Groups.php
    $isMember = $group->members()
                      ->where('group_members.user_id', $user->user_id)
                      ->exists();

    return $isLeader || $isMember;
});