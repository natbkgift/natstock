<?php

namespace App\Http\Controllers;

use App\Services\CsvPreviewService;
use App\Services\ImportProcessingException;
use App\Services\ImportResult;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportExportController extends Controller
{
    public function __construct(
        private readonly CsvPreviewService $previewService,
        private readonly ImportService $importService
    )
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
                'mimes:csv',
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

    public function process(Request $request): JsonResponse
    {
        Gate::authorize('access-staff');

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,xlsx'],
            'mode' => ['required', 'in:upsert_replace,upsert_delta,skip'],
            'strict' => ['required', 'boolean'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['file'];
        $mode = $validated['mode'];
        $isStrict = (bool) $validated['strict'];

        try {
            $result = $this->importService->process($file, $mode, $isStrict);
        } catch (ImportProcessingException $exception) {
            $response = $this->buildProcessResponse($exception->result);
            $response['message'] = $exception->getMessage();

            return response()->json($response, 422);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'file' => [$exception->getMessage()],
            ]);
        }

        $response = $this->buildProcessResponse($result);
        $response['message'] = 'นำเข้าเสร็จสิ้น';

        return response()->json($response);
    }

    public function downloadErrorCsv(Request $request): BinaryFileResponse
    {
        Gate::authorize('access-staff');

        $path = (string) $request->query('path', '');

        if ($path === '' || !str_starts_with($path, 'tmp/import_errors_')) {
            abort(404);
        }

        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path, 'error.csv');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProcessResponse(ImportResult $result): array
    {
        $errorUrl = null;

        if ($result->error_csv_path !== null) {
            $errorUrl = URL::temporarySignedRoute(
                'import_export.error_csv',
                now()->addDay(),
                ['path' => $result->error_csv_path]
            );
        }

        return [
            'summary' => [
                'products_created' => $result->products_created,
                'products_updated' => $result->products_updated,
                'batches_created' => $result->batches_created,
                'batches_updated' => $result->batches_updated,
                'movements_created' => $result->movements_created,
                'rows_ok' => $result->rows_ok,
                'rows_error' => $result->rows_error,
                'strict_rolled_back' => $result->strict_rolled_back,
            ],
            'ignored_columns' => $result->ignored_columns,
            'error_csv_url' => $errorUrl,
        ];
    }
}
