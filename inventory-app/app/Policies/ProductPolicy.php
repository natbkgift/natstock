<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isStaff();
    }
}
