<?php

namespace App\Providers;

use App\Services\AuditLogger;
use App\Support\Settings\SettingManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        // Support older MySQL variants on shared hosts (avoid 1071 key too long)
        try { Schema::defaultStringLength(191); } catch (\Throwable $e) { /* ignore */ }

        // Force https if APP_URL is https to keep session/cookie consistent behind proxies
        $appUrl = config('app.url');
        if (is_string($appUrl) && str_starts_with($appUrl, 'https://')) {
            try { URL::forceScheme('https'); } catch (\Throwable $e) { /* ignore */ }
        }

        try {
            $siteName = app(SettingManager::class)->getSiteName();
            if ($siteName !== '') {
                config(['app.name' => $siteName]);
            }
        } catch (\Throwable $e) {
            // ignore if settings table not ready yet
        }

        // Use Bootstrap pagination views (avoid Tailwind's large SVG chevrons rendering)
        try { Paginator::useBootstrap(); } catch (\Throwable $e) { /* ignore */ }
    }
}
