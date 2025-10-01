<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Policies\CategoryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Category::class => CategoryPolicy::class,
        Product::class => ProductPolicy::class,
        StockMovement::class => StockMovementPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('access-staff', fn (User $user): bool => in_array($user->role, ['admin', 'staff']));
        Gate::define('access-viewer', fn (User $user): bool => in_array($user->role, ['admin', 'staff', 'viewer']));
    }
}
