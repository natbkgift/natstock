<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isViewer();
    }

    public function view(User $user, Category $category): bool
    {
        return $user->isViewer();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isStaff();
    }
}
