<?php

namespace App\Console\Commands;

use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InventoryBackupCommand extends Command
{
    protected $signature = 'inventory:backup';

    protected $description = 'สร้างไฟล์สำรองฐานข้อมูลและไฟล์สำคัญของระบบคลังสินค้า';

    public function __construct(private readonly BackupService $backupService, private readonly AuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $filename = $this->backupService->run();
            $this->auditLogger->log('backup.created', 'สร้างไฟล์สำรองระบบผ่านคำสั่ง', ['file' => $filename]);
            Log::channel('daily')->info('สร้างไฟล์สำรองเรียบร้อย', ['file' => $filename]);
            $this->info('สร้างไฟล์สำรองเรียบร้อย: '.$filename);
        } catch (\Throwable $throwable) {
            Log::channel('daily')->error('ไม่สามารถสร้างไฟล์สำรองได้', ['message' => $throwable->getMessage()]);
            $this->error('ไม่สามารถสร้างไฟล์สำรองได้: '.$throwable->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
