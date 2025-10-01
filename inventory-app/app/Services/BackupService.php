<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public const BACKUP_DIR = 'app/backups';
    public const RETENTION = 7;

    public function run(): string
    {
        $backupPath = storage_path(self::BACKUP_DIR);
        if (! is_dir($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $timestamp = Carbon::now('Asia/Bangkok')->format('Ymd-His');
        $filename = "backup-{$timestamp}.zip";
        $fullPath = $backupPath.'/'.$filename;

        $zip = new ZipArchive();
        if ($zip->open($fullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์สำรองได้');
        }

        $zip->addFromString('database.json', json_encode($this->exportDatabase(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->addFromString('meta.json', json_encode([
            'generated_at' => Carbon::now('Asia/Bangkok')->toIso8601String(),
            'app_url' => config('app.url'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->addDirectoryToZip($zip, storage_path('app/public'), 'files/public');
        $this->addDirectoryToZip($zip, storage_path('app/tmp'), 'files/tmp');

        $zip->close();

        $this->cleanupOldBackups();

        return $filename;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $backupPath = storage_path(self::BACKUP_DIR);
        if (! is_dir($backupPath)) {
            return [];
        }

        $files = collect(File::files($backupPath))
            ->filter(fn ($file) => str_ends_with($file->getFilename(), '.zip'))
            ->sortByDesc(fn ($file) => $file->getMTime())
            ->values()
            ->map(function ($file) {
                return [
                    'name' => $file->getFilename(),
                    'size' => $file->getSize(),
                    'created_at' => Carbon::createFromTimestamp($file->getMTime())->timezone('Asia/Bangkok'),
                ];
            })
            ->all();

        return $files;
    }

    public function getPath(string $filename): string
    {
        $path = storage_path(self::BACKUP_DIR.'/'.$filename);
        if (! is_file($path)) {
            throw new RuntimeException('ไม่พบไฟล์สำรองที่ต้องการดาวน์โหลด');
        }

        return $path;
    }

    private function exportDatabase(): array
    {
        $tables = Schema::getTableListing();
        $data = [];

        foreach ($tables as $table) {
            if ($table === 'migrations') {
                continue;
            }

            $rows = DB::table($table)->get();
            $data[$table] = $rows->map(fn ($row) => (array) $row)->all();
        }

        return $data;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourcePath, string $destinationPrefix): void
    {
        if (! is_dir($sourcePath)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = trim(str_replace($sourcePath, '', $item->getPathname()), DIRECTORY_SEPARATOR);
            $targetPath = $destinationPrefix.'/'.$relativePath;

            if ($item->isDir()) {
                $zip->addEmptyDir($targetPath);
            } else {
                $zip->addFile($item->getPathname(), $targetPath);
            }
        }
    }

    private function cleanupOldBackups(): void
    {
        $backups = $this->list();
        if (count($backups) <= self::RETENTION) {
            return;
        }

        $pathsToRemove = array_slice($backups, self::RETENTION);
        foreach ($pathsToRemove as $backup) {
            $filePath = storage_path(self::BACKUP_DIR.'/'.$backup['name']);
            if (File::exists($filePath) && ! File::delete($filePath)) {
                Log::channel('daily')->warning('ลบไฟล์สำรองเก่าไม่สำเร็จ', ['path' => $filePath]);
            }
        }
    }
}
