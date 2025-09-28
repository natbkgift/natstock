<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('access-admin', fn (User $user): bool => $user->isAdmin());
        Gate::define('access-staff', fn (User $user): bool => in_array($user->role, ['admin', 'staff']));
        Gate::define('access-viewer', fn (User $user): bool => in_array($user->role, ['admin', 'staff', 'viewer']));
    }
}
