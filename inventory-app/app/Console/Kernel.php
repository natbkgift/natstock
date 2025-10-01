<?php

namespace App\Console;

use App\Console\Commands\InventoryBackupCommand;
use App\Console\Commands\InventoryScanAlertsCommand;
use App\Support\Settings\SettingManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        InventoryScanAlertsCommand::class,
        InventoryBackupCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $time = app(SettingManager::class)->getString('daily_scan_time', '08:00');

        if (! preg_match('/^\d{2}:\d{2}$/', $time)) {
            $time = '08:00';
        }

        $schedule->command('inventory:scan-alerts')->dailyAt($time)->timezone('Asia/Bangkok');
        $schedule->command('inventory:backup')->weeklyOn(1, '02:00')->timezone('Asia/Bangkok');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
