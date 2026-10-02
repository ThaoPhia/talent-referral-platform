<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAdminDashboard(User $user): bool
    {
        return $user->isAdmin();
    }

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $recruiter): bool
    {
        return $user->isAdmin();
    }

    public function approve(User $user, User $recruiter): bool
    {
        return $user->isAdmin();
    }

    public function deny(User $user, User $recruiter): bool
    {
        return $user->isAdmin();
    }
}
