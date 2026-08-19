<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Join_Requests;

class JoinRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Join_Requests $joinRequest): bool
    {
        return $user->user_id === $joinRequest->group->leader_id ||
               $user->user_id === $joinRequest->member_id ||
               $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    public function approve(User $user, Join_Requests $joinRequest): bool
    {
        return $user->user_id === $joinRequest->group->leader_id || $user->role === 'admin';
    }

    public function reject(User $user, Join_Requests $joinRequest): bool
    {
        return $user->user_id === $joinRequest->group->leader_id || $user->role === 'admin';
    }

    public function delete(User $user, Join_Requests $joinRequest): bool
    {
        return $user->user_id === $joinRequest->group->leader_id ||
               $user->user_id === $joinRequest->member_id ||
               $user->role === 'admin';
    }
}