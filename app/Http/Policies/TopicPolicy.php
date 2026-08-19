<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Topics;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Topics $topic): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'lecturer' || $user->role === 'admin';
    }

    public function update(User $user, Topics $topic): bool
    {
        $isLecturer = $topic->lecturer === $user->name;
        return $isLecturer || $user->role === 'admin';
    }

    public function delete(User $user, Topics $topic): bool
    {
        $isLecturer = $topic->lecturer === $user->name;
        return $isLecturer || $user->role === 'admin';
    }

    public function register(User $user): bool
    {
        return $user->role === 'student' && $user->is_have_group;
    }
}
