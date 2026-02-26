<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->can('manage users');
    }

    public function view(User $actor, User $target): bool
    {
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            return false;
        }

        return $actor->can('manage users');
    }

    public function create(User $actor): bool
    {
        return $actor->can('manage users') && $actor->can('assign roles');
    }

    public function update(User $actor, User $target): bool
    {
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            return false;
        }

        return $actor->can('manage users') && $actor->can('assign roles');
    }

    public function delete(User $actor, User $target): bool
    {
        if ($target->hasRole('super-admin') && ! $actor->hasRole('super-admin')) {
            return false;
        }

        return $actor->can('manage users');
    }
}
