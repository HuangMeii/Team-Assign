<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Invites;

class InvitePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Invites $invite): bool
    {
        return $user->user_id === $invite->member_id ||
               $user->user_id === $invite->leader_id ||
               $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'student';
    }

    public function accept(User $user, Invites $invite): bool
    {
        return $user->user_id === $invite->member_id;
    }

    public function reject(User $user, Invites $invite): bool
    {
        return $user->user_id === $invite->member_id;
    }

    public function delete(User $user, Invites $invite): bool
    {
        return $user->user_id === $invite->member_id ||
               $user->user_id === $invite->leader_id ||
               $user->role === 'admin';
    }
}