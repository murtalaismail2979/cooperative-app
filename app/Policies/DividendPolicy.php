<?php

namespace App\Policies;

use App\Models\Dividend;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DividendPolicy
{
    /**
     * Determine whether the user can view any dividend records.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAdminOrTreasurer();
    }

    /**
     * Determine whether the user can view the specific dividend record.
     */
    public function view(User $user, Dividend $dividend): bool
    {
        return $user->isAdminOrTreasurer() || $user->isMember();
    }

    /**
     * Determine whether the user can create dividends.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the dividend record.
     */
    public function delete(User $user, Dividend $dividend): bool
    {
        return $user->isAdmin();
    }
}
