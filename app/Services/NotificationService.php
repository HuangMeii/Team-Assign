<?php

namespace App\Services;

use App\Models\Notifications;
use App\Models\User;

class NotificationService
{
    /**
     * Create a notification
     */
    public static function create($userId, $type, $title, $message, $url = null, $data = null)
    {
        return Notifications::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'url' => $url,
            'data' => $data,
        ]);
    }

    /**
     * Notify when topic request is created
     */
    public static function topicRequestCreated($topicRequest)
    {
        $topic = $topicRequest->topic;
        $group = $topicRequest->group;
        
        // Notify lecturer
        if ($topic->subject && $topic->subject->lecturer_id) {
            self::create(
                $topic->subject->lecturer_id,
                'topic_request',
                'Yêu cầu đăng ký đề tài mới',
                "Nhóm {$group->group_name} yêu cầu đăng ký đề tài: {$topic->name}",
                route('topic_requests.index'), // Sửa lại route
                [
                    'topic_id' => $topic->topic_id,
                    'group_id' => $group->group_id,
                    'request_id' => $topicRequest->request_id,
                ]
            );
        }
    }

    /**
     * Notify when topic request is approved
     */
    public static function topicRequestApproved($topicRequest)
    {
        $topic = $topicRequest->topic;
        $group = $topicRequest->group;
        
        // Notify group leader
        self::create(
            $group->leader_id,
            'topic_approved',
            'Đề tài đã được duyệt',
            "Đề tài '{$topic->name}' của nhóm {$group->group_name} đã được phê duyệt!",
            route('user.my_topics'), // Sửa từ my-topics thành my_topics
            [
                'topic_id' => $topic->topic_id,
                'group_id' => $group->group_id,
            ]
        );

        // Notify all group members
        foreach ($group->members as $member) {
            self::create(
                $member->user_id,
                'topic_approved',
                'Đề tài đã được duyệt',
                "Đề tài '{$topic->name}' của nhóm {$group->group_name} đã được phê duyệt!",
                route('user.my_topics'), // Sửa từ my-topics thành my_topics
                [
                    'topic_id' => $topic->topic_id,
                    'group_id' => $group->group_id,
                ]
            );
        }
    }

    /**
     * Notify when topic request is rejected
     */
    public static function topicRequestRejected($topicRequest, $reason = null)
    {
        $topic = $topicRequest->topic;
        $group = $topicRequest->group;
        
        $message = "Đề tài '{$topic->name}' của nhóm {$group->group_name} đã bị từ chối";
        if ($reason) {
            $message .= ". Lý do: {$reason}";
        }
        
        // Notify group leader
        self::create(
            $group->leader_id,
            'topic_rejected',
            'Đề tài bị từ chối',
            $message,
            route('user.topics'),
            [
                'topic_id' => $topic->topic_id,
                'group_id' => $group->group_id,
                'reason' => $reason,
            ]
        );
    }

    /**
     * Notify when join request is created
     */
    public static function joinRequestCreated($joinRequest)
    {
        $group = $joinRequest->group;
        $member = $joinRequest->member;
        
        // Notify group leader
        self::create(
            $group->leader_id,
            'join_request',
            'Yêu cầu tham gia nhóm',
            "{$member->name} muốn tham gia nhóm {$group->group_name}",
            route('user.group_detail', $group->group_id),
            [
                'group_id' => $group->group_id,
                'member_id' => $member->user_id,
                'member_name' => $member->name,
            ]
        );
    }

    /**
     * Notify when join request is approved
     */
    public static function joinRequestApproved($joinRequest)
    {
        $group = $joinRequest->group;
        
        // Notify member
        self::create(
            $joinRequest->member_id,
            'group_joined',
            'Đã được chấp nhận vào nhóm',
            "Bạn đã được chấp nhận tham gia nhóm {$group->group_name}!",
            route('user.group_detail', $group->group_id),
            [
                'group_id' => $group->group_id,
                'group_name' => $group->group_name,
            ]
        );
    }

    /**
     * Notify when invited to group
     */
    public static function groupInviteCreated($invite)
    {
        // Load relationships nếu chưa có
        if (!$invite->relationLoaded('group')) {
            $invite->load('group');
        }
        if (!$invite->relationLoaded('inviter')) {
            $invite->load('inviter');
        }
        
        $group = $invite->group;
        $inviter = $invite->inviter;
        
        // Notify invited member
        self::create(
            $invite->member_id,
            'invite',
            'Lời mời tham gia nhóm',
            "{$inviter->name} mời bạn tham gia nhóm {$group->group_name}",
            route('user.invites'), // Đã đúng rồi
            [
                'group_id' => $group->group_id,
                'group_name' => $group->group_name,
                'invite_id' => $invite->id,
                'inviter_name' => $inviter->name,
            ]
        );
    }

    /**
     * Mark all notifications as read for a user
     */
    public static function markAllAsRead($userId)
    {
        Notifications::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    /**
     * Delete old read notifications
     */
    public static function cleanupOldNotifications($days = 30)
    {
        Notifications::where('is_read', true)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}