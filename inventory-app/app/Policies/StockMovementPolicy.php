<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, StockMovement $movement): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }
}
