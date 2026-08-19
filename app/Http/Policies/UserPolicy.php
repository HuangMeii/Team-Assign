<?php
namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, User $model): bool
    {
        return $user->user_id === $model->user_id || $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        return $user->user_id === $model->user_id || $user->role === 'admin';
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->user_id !== $model->user_id;
    }
}