<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\User;

class ProductBatchPolicy
{
    public function viewAny(User $user, Product $product): bool
    {
        return $user->isViewer();
    }

    public function create(User $user, Product $product): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, ProductBatch $batch): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, ProductBatch $batch): bool
    {
        return $user->isAdmin();
    }
}
