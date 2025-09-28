<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use App\Services\BackupService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(private readonly BackupService $backupService, private readonly AuditLogger $auditLogger)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('access-admin');

        return view('admin.backup.index', [
            'backups' => $this->backupService->list(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('access-admin');

        try {
            $filename = $this->backupService->run();
            $this->auditLogger->log('backup.created', 'สร้างไฟล์สำรองจากหน้าจัดการ', ['file' => $filename], null, $request->user());
            return redirect()->route('admin.backup.index')->with('status', 'สร้างไฟล์สำรองสำเร็จ: '.$filename);
        } catch (\Throwable $throwable) {
            Log::channel('daily')->error('ไม่สามารถสร้างไฟล์สำรองได้', ['message' => $throwable->getMessage()]);

            return redirect()->route('admin.backup.index')->with('error', 'ไม่สามารถสร้างไฟล์สำรองได้: '.$throwable->getMessage());
        }
    }

    public function download(string $filename): BinaryFileResponse
    {
        Gate::authorize('access-admin');

        $path = $this->backupService->getPath($filename);

        return response()->download($path, $filename);
    }
}
