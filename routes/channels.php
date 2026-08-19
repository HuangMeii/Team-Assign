<?php


use App\Models\Groups;
use Illuminate\Support\Facades\Broadcast;
Broadcast::channel('App.Models.User. {id}', function ($user, $id) {
    return (int) $user->user_id === (int) $id;
});

Broadcast::channel('chat.group.{groupId}', function ($user, $groupId) {
    $group = Groups::find($groupId);

    if (!$group) {
        return false;
    }

    // Kiểm tra user là leader hoặc là thành viên của nhóm
    $isLeader = $group->leader_id === $user->user_id;
    // Sử dụng quan hệ members() đã định nghĩa trong Groups.php
    $isMember = $group->members()
                      ->where('group_members.user_id', $user->user_id) 
                      ->exists();

    return $isLeader || $isMember;
});