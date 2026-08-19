<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Groups;

class GroupsPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Groups $group): bool
    {
        $isMember = $group->members()->where('user_id', $user->user_id)->exists();
        $isLeader = $group->leader_id === $user->user_id;
        $isAdmin = $user->role === 'admin';

        return $isMember || $isLeader || $isAdmin;
    }

    public function create(User $user): bool
    {
        return $user->role === 'student' && !$user->is_have_group;
    }

    public function update(User $user, Groups $group): bool
    {
        return $user->user_id === $group->leader_id || $user->role === 'admin';
    }

    public function delete(User $user, Groups $group): bool
    {
        return $user->user_id === $group->leader_id || $user->role === 'admin';
    }

    public function addMember(User $user, Groups $group): bool
    {
        return $user->user_id === $group->leader_id || $user->role === 'admin';
    }

    public function removeMember(User $user, Groups $group): bool
    {
        return $user->user_id === $group->leader_id || $user->role === 'admin';
    }
}