<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportRequest;
use App\Services\ImportService;
use Illuminate\Http\Request;
use RuntimeException;

class ImportController extends Controller
{
    public function __construct(private readonly ImportService $service)
    {
    }

    public function index()
    {
        return view('admin.import.index');
    }

    public function preview(ImportRequest $request)
    {
        $result = $this->service->preview(
            $request->uploadedFile(),
            $request->duplicateMode(),
            $request->autoCreateCategory()
        );

        return view('admin.import.preview', [
            'summary' => $result['summary'],
            'previewRows' => $result['preview_rows'],
            'fileToken' => $result['file_token'],
            'canCommit' => $result['can_commit'],
            'fileError' => $result['file_error'],
        ]);
    }

    public function commit(Request $request)
    {
        $result = $this->service->commit(
            (string) $request->input('file_token', ''),
            strtoupper((string) $request->input('duplicate_mode', 'UPSERT')),
            $request->boolean('auto_create_category'),
            $request->user()
        );

        return view('admin.import.result', [
            'summary' => $result['summary'],
            'errorUrl' => $result['error_url'] ?? null,
        ]);
    }

    public function downloadErrors(string $token)
    {
        $path = $this->service->resolveErrorFilePath($token);

        if (!is_file($path)) {
            throw new RuntimeException('ไม่พบไฟล์ข้อผิดพลาดสำหรับดาวน์โหลด');
        }

        return response()->download($path, 'error.csv');
    }
}
