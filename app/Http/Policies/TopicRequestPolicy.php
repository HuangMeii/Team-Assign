<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Topic_requests;

class TopicRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'lecturer' || $user->role === 'lecturer';
    }

    public function view(User $user, Topic_requests $topicRequest): bool
    {
        return $user->role === 'lecturer' || 
               $topicRequest->topic->lecturer === $user->name ||
               $topicRequest->created_by === $user->user_id;
    }

    public function create(User $user): bool
    {
        return $user->role === 'student' && $user->is_have_group;
    }

    public function approve(User $user, Topic_requests $topicRequest): bool
    {
        return $user->role === 'lecturer' || $topicRequest->topic->lecturer === $user->name;
    }

    public function reject(User $user, Topic_requests $topicRequest): bool
    {
        return $user->role === 'lecturer' || $topicRequest->topic->lecturer === $user->name;
    }

    public function delete(User $user, Topic_requests $topicRequest): bool
    {
        return $user->role === 'lecturer' || 
               $topicRequest->created_by === $user->user_id ||
               $topicRequest->topic->lecturer === $user->name;
    }

}
