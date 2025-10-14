<?php

namespace App\Http\Controllers;

use App\Services\CsvPreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

class ImportExportController extends Controller
{
    public function __construct(private readonly CsvPreviewService $previewService)
    {
    }

    public function index(Request $request): View
    {
        Gate::authorize('access-staff');

        return view('import_export.index');
    }

    public function preview(Request $request): JsonResponse
    {
        Gate::authorize('access-staff');

        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                function (string $attribute, mixed $value, callable $fail): void {
                    if (! $value instanceof UploadedFile) {
                        return;
                    }

                    if (strtolower($value->getClientOriginalExtension()) !== 'csv') {
                        $fail('กรุณาเลือกไฟล์นามสกุล .csv เท่านั้น');
                    }
                },
            ],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];

        try {
            $preview = $this->previewService->preview($file);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'file' => [$e->getMessage()],
            ]);
        }

        $html = view('import_export.partials.preview_table', [
            'preview' => $preview,
        ])->render();

        return response()->json([
            'html' => $html,
            'meta' => [
                'total_rows' => $preview['total_rows'],
                'ignored_columns' => $preview['ignored_columns'],
            ],
        ]);
    }
}
