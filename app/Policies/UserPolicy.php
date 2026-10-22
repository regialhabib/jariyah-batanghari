<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * Admin tidak boleh menonaktifkan akunnya sendiri.
     */
    public function toggleAktif(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
