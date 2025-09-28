<?php
namespace App\Http\Controllers;

use App\Services\ImportService;

class ImportController extends Controller
{
    public function __construct(protected ImportService $service = new ImportService())
    {
    }

    public function index(): void
    {
        view('import/index');
    }

    public function preview(): void
    {
        verify_csrf();
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'กรุณาเลือกไฟล์นำเข้า');
            redirect('/admin/import');
        }

        $path = storage_path('imports/'.uniqid('upload_', true));
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        move_uploaded_file($_FILES['file']['tmp_name'], $path);

        $_SESSION['_import_file'] = $path;
        $rows = $this->service->preview($path);
        view('import/preview', ['rows' => $rows]);
    }

    public function process(): void
    {
        verify_csrf();
        $mode = $_POST['mode'] ?? 'upsert';
        $autoCategory = isset($_POST['auto_category']);
        $path = $_SESSION['_import_file'] ?? null;
        if (!$path || !file_exists($path)) {
            flash('error', 'ไม่พบไฟล์สำหรับนำเข้า');
            redirect('/admin/import');
        }

        $results = $this->service->import($path, $mode, $autoCategory, auth()->user()->id);
        unlink($path);
        unset($_SESSION['_import_file']);

        $_SESSION['_import_result'] = $results;
        redirect('/admin/import/result');
    }

    public function result(): void
    {
        $result = $_SESSION['_import_result'] ?? null;
        if (!$result) {
            redirect('/admin/import');
        }
        view('import/result', ['result' => $result]);
    }

    public function downloadErrors(): void
    {
        $result = $_SESSION['_import_result'] ?? null;
        if (!$result || empty($result['errors'])) {
            redirect('/admin/import');
        }

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="import_errors.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, array_merge(['line', 'message'], $this->service->previewHeaders()));
        foreach ($result['errors'] as $error) {
            fputcsv($output, array_merge([$error['line'], $error['message']], $error['row']));
        }
        fclose($output);
        exit;
    }
}
