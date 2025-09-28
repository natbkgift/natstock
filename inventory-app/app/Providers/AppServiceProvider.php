<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Support\Settings\SettingManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void
    {
        $this->app->singleton(SettingManager::class, function (): SettingManager {
            return new SettingManager();
        });

        $this->app->singleton(AuditLogger::class, function (): AuditLogger {
            return new AuditLogger();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
