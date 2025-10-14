<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\PriceGuard;
use Carbon\Carbon;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Throwable;
use ZipArchive;
use function storage_path;

class ImportService
{
    private const TEMPLATE_COLUMNS = [
        'sku',
        'name',
        'category',
        'qty',
        'cost_price',
        'sale_price',
        'expire_date',
        'reorder_point',
        'note',
        'is_active',
    ];

    private const CHUNK_SIZE = 500;

    private const PRICE_COLUMNS = ['cost_price', 'sale_price'];

    private readonly string $storagePath;

    private bool $priceColumnsDetected = false;

    public function __construct()
    {
        $this->storagePath = storage_path('app/tmp');
    }

    /**
     * @return array{summary: array<string, mixed>, preview_rows: list<array<string, mixed>>, file_token: string, can_commit: bool, file_error: string|null}
     */
    public function preview(mixed $uploadedFile, string $duplicateMode, bool $autoCreateCategory): array
    {
        $originalName = $this->resolveOriginalName($uploadedFile);
        $summary = [
            'original_name' => $originalName,
            'total_rows' => 0,
            'valid_rows' => 0,
            'error_rows' => 0,
            'duplicate_mode' => $duplicateMode,
            'auto_create_category' => $autoCreateCategory,
        ];

        $this->priceColumnsDetected = false;

        if ($uploadedFile === null) {
            return [
                'summary' => $summary,
                'preview_rows' => [],
                'file_token' => '',
                'can_commit' => false,
                'file_error' => 'ไม่พบไฟล์ที่อัปโหลด กรุณาลองใหม่อีกครั้ง',
            ];
        }

        $extension = $this->guessExtension($uploadedFile);

        try {
            $storedPath = $this->storeUploadedFile($uploadedFile, $extension);
        } catch (Throwable $throwable) {
            return [
                'summary' => $summary,
                'preview_rows' => [],
                'file_token' => '',
                'can_commit' => false,
                'file_error' => $throwable->getMessage(),
            ];
        }

        $previewRows = [];
        $fileError = null;

        try {
            foreach ($this->readRows($storedPath, $extension) as $row) {
                $summary['total_rows']++;
                if ($this->isRowCompletelyEmpty($row['values'])) {
                    continue;
                }

                $validation = $this->validateRow($row['values']);
                $errors = $validation['errors'];
                $normalized = $validation['normalized'];

                if ($errors === []) {
                    $summary['valid_rows']++;
                } else {
                    $summary['error_rows']++;
                }

                if (count($previewRows) < 20) {
                    $previewRows[] = [
                        'row_number' => $row['row_number'],
                        'errors' => $errors,
                        'normalized' => $this->formatPreviewValues($normalized),
                    ];
                }
            }
        } catch (Throwable $throwable) {
            $fileError = $throwable->getMessage();
            $summary['total_rows'] = 0;
            $summary['valid_rows'] = 0;
            $summary['error_rows'] = 0;
            $previewRows = [];
        }

        $canCommit = $fileError === null && $summary['total_rows'] > 0 && $summary['error_rows'] === 0;

        if (!config('inventory.enable_price') && $this->priceColumnsDetected) {
            $summary['price_columns_ignored'] = true;
        }

        return [
            'summary' => $summary,
            'preview_rows' => $previewRows,
            'file_token' => $fileError ? '' : basename($storedPath),
            'can_commit' => $canCommit,
            'file_error' => $fileError,
        ];
    }

    /**
     * @return array{summary: array<string, int>, error_rows: list<array<string, mixed>>, error_url: ?string}
     */
    public function commit(string $fileToken, string $duplicateMode, bool $autoCreateCategory, ?User $actor = null): array
    {
        $filePath = $this->resolveStoredPath($fileToken);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $this->priceColumnsDetected = false;

        $summary = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $validRows = [];
        $errorRows = [];

        try {
            foreach ($this->readRows($filePath, $extension) as $row) {
                if ($this->isRowCompletelyEmpty($row['values'])) {
                    continue;
                }

                $validation = $this->validateRow($row['values']);
                if ($validation['errors'] !== []) {
                    $summary['errors']++;
                    $errorRows[] = $this->buildErrorRow($row['row_number'], $validation['errors'], $row['values']);
                    continue;
                }

                $validRows[] = [
                    'row_number' => $row['row_number'],
                    'normalized' => $validation['normalized'],
                    'raw' => $row['values'],
                ];
            }
        } catch (Throwable $throwable) {
            $summary['errors'] = max($summary['errors'], 1);
            $errorRows[] = [
                'row_number' => 0,
                'error_messages' => $throwable->getMessage(),
            ];

            return [
                'summary' => $summary,
                'error_rows' => $errorRows,
                'error_url' => null,
            ];
        }

        if ($validRows !== []) {
            foreach (array_chunk($validRows, self::CHUNK_SIZE) as $chunk) {
                DB::transaction(function () use (&$summary, &$errorRows, $chunk, $duplicateMode, $autoCreateCategory, $actor): void {
                    foreach ($chunk as $rowData) {
                        try {
                            $result = $this->upsertOrSkip($rowData['normalized'], $duplicateMode, $autoCreateCategory, $actor);
                            $summary[$result]++;
                        } catch (RuntimeException $exception) {
                            $summary['errors']++;
                            $errorRows[] = $this->buildErrorRow($rowData['row_number'], [$exception->getMessage()], $rowData['raw']);
                        }
                    }
                });
            }
        }

        $errorToken = $this->exportErrors($errorRows);
        $errorUrl = null;

        if ($errorToken !== null) {
            $errorUrl = URL::temporarySignedRoute(
                'admin.import.errors.download',
                now()->addDays(1),
                ['token' => $errorToken]
            );
        }

        return [
            'summary' => $summary,
            'error_rows' => $errorRows,
            'error_url' => $errorUrl,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array{errors: list<string>, normalized: array<string, mixed>}
     */
    private function validateRow(array $row): array
    {
        $errors = [];
        $normalized = [];
        $pricingEnabled = (bool) config('inventory.enable_price');

        $sku = trim((string) ($row['sku'] ?? ''));
        if ($sku === '') {
            $errors[] = 'sku: ต้องระบุค่า';
        } elseif (!preg_match('/^[A-Za-z0-9._-]{1,64}$/', $sku)) {
            $errors[] = 'sku: ต้องเป็นตัวอักษรหรือตัวเลข (._-) และไม่เกิน 64 ตัวอักษร';
        } else {
            $normalized['sku'] = $sku;
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $errors[] = 'name: ต้องระบุค่า';
        } else {
            $normalized['name'] = $name;
        }

        $category = trim((string) ($row['category'] ?? ''));
        if ($category === '') {
            $errors[] = 'category: ต้องระบุค่า';
        } else {
            $normalized['category_name'] = $category;
        }

        $qtyRaw = $this->normalizeNumeric($row['qty'] ?? null);
        if ($qtyRaw === null || $qtyRaw < 0) {
            $errors[] = 'qty: ต้องเป็นตัวเลขและมากกว่าหรือเท่ากับ 0';
        } else {
            $normalized['qty'] = (int) round($qtyRaw);
        }

        if ($pricingEnabled) {
            $costRaw = $this->normalizeNumeric($row['cost_price'] ?? null, 2);
            if ($costRaw === null || $costRaw < 0) {
                $errors[] = 'cost_price: ต้องเป็นตัวเลขและมากกว่าหรือเท่ากับ 0';
            } else {
                $normalized['cost_price'] = number_format($costRaw, 2, '.', '');
            }

            $saleRaw = $this->normalizeNumeric($row['sale_price'] ?? null, 2);
            if ($saleRaw !== null && $saleRaw < 0) {
                $errors[] = 'sale_price: ต้องเป็นตัวเลขและมากกว่าหรือเท่ากับ 0';
            } else {
                $normalized['sale_price'] = $saleRaw !== null ? number_format($saleRaw, 2, '.', '') : null;
            }
        } else {
            $normalized['cost_price'] = null;
            $normalized['sale_price'] = null;
        }

        $expire = trim((string) ($row['expire_date'] ?? ''));
        if ($expire !== '') {
            $immutable = DateTimeImmutable::createFromFormat('!Y-m-d', $expire);
            $dateErrors = DateTimeImmutable::getLastErrors() ?: ['warning_count' => 0, 'error_count' => 0];
            if ($immutable === false || ($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0 || $immutable->format('Y-m-d') !== $expire) {
                $errors[] = 'expire_date: รูปแบบวันที่ต้องเป็น YYYY-MM-DD';
            } else {
                $normalized['expire_date'] = Carbon::instance($immutable);
            }
        } else {
            $normalized['expire_date'] = null;
        }

        $reorderRaw = $this->normalizeNumeric($row['reorder_point'] ?? null);
        if ($reorderRaw !== null && $reorderRaw < 0) {
            $errors[] = 'reorder_point: ต้องเป็นตัวเลขและมากกว่าหรือเท่ากับ 0';
        } else {
            $normalized['reorder_point'] = $reorderRaw !== null ? (int) round($reorderRaw) : null;
        }

        $note = trim((string) ($row['note'] ?? ''));
        $normalized['note'] = $note === '' ? null : $note;

        $isActiveRaw = trim((string) ($row['is_active'] ?? ''));
        if ($isActiveRaw === '') {
            $normalized['is_active'] = true;
        } elseif (!in_array($isActiveRaw, ['1', '0', 1, 0], true)) {
            $errors[] = 'is_active: ต้องเป็น 1 หรือ 0';
        } else {
            $normalized['is_active'] = (string) $isActiveRaw === '1' || $isActiveRaw === 1;
        }

        return [
            'errors' => $errors,
            'normalized' => $normalized,
        ];
    }

    private function upsertOrSkip(array $row, string $mode, bool $autoCreateCategory, ?User $actor): string
    {
        $category = Category::query()->firstWhere('name', $row['category_name']);

        if ($category === null) {
            if (! $autoCreateCategory) {
                throw new RuntimeException('ไม่พบหมวดหมู่ ' . $row['category_name']);
            }

            $category = Category::query()->create([
                'name' => $row['category_name'],
            ]);
        }

        $product = Product::query()->where('sku', $row['sku'])->first();

        if ($product === null) {
            $productData = [
                'sku' => $row['sku'],
                'name' => $row['name'],
                'note' => $row['note'],
                'category_id' => $category->getKey(),
                'cost_price' => $row['cost_price'] ?? null,
                'sale_price' => $row['sale_price'] ?? null,
                'expire_date' => $row['expire_date'],
                'reorder_point' => $row['reorder_point'],
                'qty' => $row['qty'],
                'is_active' => $row['is_active'],
            ];
            PriceGuard::strip($productData);

            $product = Product::create($productData);

            $this->createMovement($product, 'receive', $row['qty'], 'import:create', $actor);

            return 'created';
        }

        if ($mode === 'SKIP') {
            return 'skipped';
        }

        $previousQty = (int) ($product->qty ?? 0);
        $newQty = (int) $row['qty'];
        $delta = $newQty - $previousQty;

        $updateData = [
            'name' => $row['name'],
            'note' => $row['note'],
            'category_id' => $category->getKey(),
            'cost_price' => $row['cost_price'] ?? null,
            'sale_price' => $row['sale_price'] ?? null,
            'expire_date' => $row['expire_date'],
            'reorder_point' => $row['reorder_point'],
            'qty' => $newQty,
            'is_active' => $row['is_active'],
        ];
        PriceGuard::strip($updateData);

        $product->update($updateData);

        if ($delta > 0) {
            $this->createMovement($product, 'receive', $delta, $this->buildAdjustNote($delta), $actor);
        } elseif ($delta < 0) {
            $this->createMovement($product, 'issue', abs($delta), $this->buildAdjustNote($delta), $actor);
        }

        return 'updated';
    }

    private function buildAdjustNote(int $delta): string
    {
        $formatted = $delta > 0 ? '+' . $delta : (string) $delta;

        return 'ปรับยอดจากนำเข้าไฟล์ (Δ' . $formatted . ')';
    }

    private function createMovement(Product $product, string $type, int $qty, string $note, ?User $actor = null): void
    {
        StockMovement::create([
            'product_id' => $product->getKey(),
            'type' => $type,
            'qty' => $qty,
            'note' => $note,
            'actor_id' => $actor?->getKey(),
            'happened_at' => now(),
        ]);
    }

    /**
     * @return iterable<int, array{row_number: int, values: array<string, mixed>}>|array
     */
    private function readRows(string $path, string $extension): iterable
    {
        return match ($extension) {
            'csv' => $this->readCsvRows($path),
            'xlsx' => $this->readXlsxRows($path),
            default => throw new RuntimeException('รูปแบบไฟล์ไม่รองรับ'),
        };
    }

    private function readCsvRows(string $path): iterable
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('ไม่สามารถเปิดไฟล์ CSV ได้');
        }

        try {
            $header = fgetcsv($handle);
            if ($header === false) {
                throw new RuntimeException('ไม่พบข้อมูลในไฟล์ CSV');
            }

            $header = $this->sanitizeHeader($header);
            $indexes = $this->resolveColumnIndexes($header);

            $rowNumber = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $values = [];
                foreach (self::TEMPLATE_COLUMNS as $column) {
                    $index = $indexes[$column];
                    if ($index === null) {
                        $values[$column] = null;
                        continue;
                    }

                    $values[$column] = array_key_exists($index, $row) ? trim((string) $row[$index]) : null;
                }

                yield ['row_number' => $rowNumber, 'values' => $values];
            }
        } finally {
            fclose($handle);
        }
    }

    private function readXlsxRows(string $path): iterable
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('ไม่สามารถเปิดไฟล์ XLSX ได้');
        }

        try {
            $sharedStrings = $this->parseSharedStrings($zip->getFromName('xl/sharedStrings.xml') ?: '');
            $sheetContent = $zip->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetContent === false) {
                throw new RuntimeException('ไม่พบข้อมูลในไฟล์ XLSX');
            }

            $sheet = simplexml_load_string($sheetContent);
            if ($sheet === false) {
                throw new RuntimeException('ไม่สามารถอ่านข้อมูลในไฟล์ XLSX ได้');
            }

            $rows = $sheet->sheetData->row ?? [];
            if (count($rows) === 0) {
                throw new RuntimeException('ไม่พบข้อมูลในไฟล์ XLSX');
            }

            $headerRow = $this->extractRowValues($rows[0]->c ?? [], $sharedStrings);
            $header = $this->sanitizeHeader($headerRow);
            $indexes = $this->resolveColumnIndexes($header);

            foreach ($rows as $rowElement) {
                $rowIndex = (int) $rowElement['r'];
                if ($rowIndex === 1) {
                    continue;
                }

                $cells = $this->extractRowValues($rowElement->c ?? [], $sharedStrings);
                $values = [];
                foreach (self::TEMPLATE_COLUMNS as $column) {
                    $position = $indexes[$column];
                    if ($position === null) {
                        $values[$column] = null;
                        continue;
                    }

                    $values[$column] = array_key_exists($position, $cells) ? trim((string) $cells[$position]) : null;
                }

                yield ['row_number' => $rowIndex, 'values' => $values];
            }
        } finally {
            $zip->close();
        }
    }

    private function sanitizeHeader(array $header): array
    {
        if ($header === []) {
            throw new RuntimeException('ไม่พบหัวคอลัมน์ในไฟล์');
        }

        if (isset($header[0])) {
            $header[0] = ltrim((string) $header[0], "\xEF\xBB\xBF");
        }

        $normalized = array_map(fn ($value) => trim((string) $value), $header);

        if (in_array('cost_price', $normalized, true) || in_array('sale_price', $normalized, true)) {
            $this->priceColumnsDetected = true;
        }

        $missing = array_diff(self::TEMPLATE_COLUMNS, $normalized);

        if ($missing !== []) {
            if (!config('inventory.enable_price')) {
                $missing = array_diff($missing, self::PRICE_COLUMNS);
            }

            if ($missing !== []) {
                throw new RuntimeException('หัวคอลัมน์ไม่ครบตามเทมเพลต: ' . implode(', ', $missing));
            }
        }

        return $normalized;
    }

    /**
     * @param array<int, mixed> $header
     * @return array<string, int>
     */
    private function resolveColumnIndexes(array $header): array
    {
        $indexes = [];
        foreach (self::TEMPLATE_COLUMNS as $column) {
            $position = array_search($column, $header, true);
            if ($position === false) {
                if (!config('inventory.enable_price') && in_array($column, self::PRICE_COLUMNS, true)) {
                    $indexes[$column] = null;
                    continue;
                }

                throw new RuntimeException('หัวคอลัมน์ไม่ครบตามเทมเพลต: ' . $column);
            }
            $indexes[$column] = (int) $position;
        }

        return $indexes;
    }

    private function parseSharedStrings(string $xmlContent): array
    {
        if ($xmlContent === '') {
            return [];
        }

        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            return [];
        }

        $strings = [];
        foreach ($xml->si as $index => $si) {
            if (isset($si->t)) {
                $strings[(int) $index] = (string) $si->t;
                continue;
            }

            $text = '';
            foreach ($si->r as $richText) {
                $text .= (string) $richText->t;
            }
            $strings[(int) $index] = $text;
        }

        return $strings;
    }

    private function extractRowValues($cells, array $sharedStrings): array
    {
        $values = [];
        foreach ($cells as $cell) {
            $reference = (string) $cell['r'];
            if ($reference === '') {
                continue;
            }
            $columnIndex = $this->columnNameToIndex($reference);
            $type = (string) $cell['t'];

            if ($type === 's') {
                $stringIndex = (int) $cell->v;
                $values[$columnIndex] = $sharedStrings[$stringIndex] ?? '';
            } elseif ($type === 'inlineStr') {
                $values[$columnIndex] = (string) ($cell->is->t ?? '');
            } else {
                $values[$columnIndex] = (string) $cell->v;
            }
        }

        if ($values === []) {
            return [];
        }

        ksort($values);

        return array_values($values);
    }

    private function columnNameToIndex(string $cellReference): int
    {
        if (!preg_match('/([A-Z]+)(\d+)/', $cellReference, $matches)) {
            return 0;
        }

        $letters = $matches[1];
        $length = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $length; $i++) {
            $index *= 26;
            $index += ord($letters[$i]) - 64;
        }

        return $index - 1;
    }

    private function normalizeNumeric(mixed $value, int $decimals = 2): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $raw = str_replace([',', ' '], ['', ''], (string) $value);
        if (!is_numeric($raw)) {
            return null;
        }

        return round((float) $raw, $decimals);
    }

    /**
     * @param array<string, mixed> $normalized
     * @return array<string, mixed>
     */
    private function formatPreviewValues(array $normalized): array
    {
        $formatted = [];

        $formatted['sku'] = $this->escapeForDisplay($normalized['sku'] ?? null);
        $formatted['name'] = $this->escapeForDisplay($normalized['name'] ?? null);
        $formatted['category'] = $this->escapeForDisplay($normalized['category_name'] ?? null);
        $formatted['qty'] = $normalized['qty'] ?? null;

        if (config('inventory.enable_price')) {
            $formatted['cost_price'] = isset($normalized['cost_price']) ? number_format((float) $normalized['cost_price'], 2, '.', '') : null;
            $formatted['sale_price'] = isset($normalized['sale_price']) && $normalized['sale_price'] !== null ? number_format((float) $normalized['sale_price'], 2, '.', '') : null;
        }

        $formatted['expire_date'] = isset($normalized['expire_date']) && $normalized['expire_date'] instanceof Carbon ? $normalized['expire_date']->toDateString() : null;
        $formatted['reorder_point'] = $normalized['reorder_point'] ?? null;
        $formatted['note'] = $this->escapeForDisplay($normalized['note'] ?? null);
        $formatted['is_active'] = isset($normalized['is_active']) ? ($normalized['is_active'] ? '1' : '0') : null;

        foreach ($formatted as $key => $value) {
            $formatted[$key] = $value === null || $value === '' ? '—' : $this->escapeForDisplay($value);
        }

        return $formatted;
    }

    private function escapeForDisplay(mixed $value): string
    {
        $string = (string) $value;
        if ($string === '') {
            return '';
        }

        $first = $string[0];
        if (in_array($first, ['=', '+', '-', '@'], true)) {
            return "'" . $string;
        }

        return $string;
    }

    private function isRowCompletelyEmpty(array $row): bool
    {
        foreach (self::TEMPLATE_COLUMNS as $column) {
            if (isset($row[$column]) && trim((string) $row[$column]) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string> $errors
     * @param array<string, mixed> $raw
     * @return array<string, mixed>
     */
    private function buildErrorRow(int $rowNumber, array $errors, array $raw): array
    {
        $result = [
            'row_number' => $rowNumber,
            'error_messages' => implode('; ', $errors),
        ];

        foreach (self::TEMPLATE_COLUMNS as $column) {
            $rawValue = $raw[$column] ?? '';
            $result['raw_' . $column] = $rawValue === null ? '' : $this->escapeForDisplay($rawValue);
        }

        return $result;
    }

    /**
     * @param list<array<string, mixed>> $errorRows
     */
    private function exportErrors(array $errorRows): ?string
    {
        if ($errorRows === []) {
            return null;
        }

        $this->ensureStorageDirectory();
        $token = 'import_errors_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.csv';
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $token;

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('ไม่สามารถสร้างไฟล์ error.csv ได้');
        }

        $header = [
            'row_number',
            'error_messages',
            'raw_sku',
            'raw_name',
            'raw_category',
            'raw_qty',
            'raw_cost_price',
            'raw_sale_price',
            'raw_expire_date',
            'raw_reorder_point',
            'raw_note',
            'raw_is_active',
        ];

        fputcsv($handle, $header);

        foreach ($errorRows as $row) {
            $record = [
                $row['row_number'] ?? '',
                $row['error_messages'] ?? '',
            ];

            foreach (self::TEMPLATE_COLUMNS as $column) {
                $record[] = $row['raw_' . $column] ?? '';
            }

            fputcsv($handle, $record);
        }

        fclose($handle);

        return $token;
    }

    public function resolveErrorFilePath(string $token): string
    {
        $token = basename($token);

        return $this->storagePath . DIRECTORY_SEPARATOR . $token;
    }

    private function resolveOriginalName(mixed $file): string
    {
        if (is_object($file)) {
            if (method_exists($file, 'getClientOriginalName')) {
                return (string) $file->getClientOriginalName();
            }

            if (method_exists($file, 'getFilename')) {
                return (string) $file->getFilename();
            }
        }

        if (is_string($file)) {
            return basename($file);
        }

        return 'import.' . $this->guessExtension($file, default: 'csv');
    }

    private function guessExtension(mixed $file, string $default = 'csv'): string
    {
        if (is_object($file) && method_exists($file, 'getClientOriginalExtension')) {
            $extension = (string) $file->getClientOriginalExtension();
            if ($extension !== '') {
                return strtolower($extension);
            }
        }

        if (is_object($file) && method_exists($file, 'getMimeType')) {
            $mime = (string) $file->getMimeType();
            if ($mime === 'text/csv') {
                return 'csv';
            }
        }

        if (is_object($file) && method_exists($file, 'getRealPath')) {
            $extension = pathinfo((string) $file->getRealPath(), PATHINFO_EXTENSION);
            if ($extension !== '') {
                return strtolower($extension);
            }
        }

        if (is_string($file)) {
            $extension = pathinfo($file, PATHINFO_EXTENSION);
            if ($extension !== '') {
                return strtolower($extension);
            }
        }

        return $default;
    }

    private function resolveStoredPath(string $fileToken): string
    {
        $fileToken = basename($fileToken);
        $path = $this->storagePath . DIRECTORY_SEPARATOR . $fileToken;

        if (!is_file($path)) {
            throw new RuntimeException('ไม่พบไฟล์ชั่วคราวสำหรับการนำเข้า');
        }

        return $path;
    }

    private function ensureStorageDirectory(): void
    {
        if (!is_dir($this->storagePath) && !@mkdir($this->storagePath, 0755, true) && !is_dir($this->storagePath)) {
            throw new RuntimeException('ไม่สามารถสร้างไดเรกทอรีจัดเก็บไฟล์ชั่วคราวได้');
        }
    }

    private function storeUploadedFile(mixed $file, string $extension): string
    {
        $this->ensureStorageDirectory();

        $token = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($extension);
        $destination = $this->storagePath . DIRECTORY_SEPARATOR . $token;

        $sourcePath = $this->resolveRealPath($file);
        if (!copy($sourcePath, $destination)) {
            throw new RuntimeException('ไม่สามารถจัดเก็บไฟล์ชั่วคราวได้');
        }

        return $destination;
    }

    private function resolveRealPath(mixed $file): string
    {
        if (is_object($file)) {
            if (method_exists($file, 'getRealPath')) {
                $path = $file->getRealPath();
                if ($path !== false) {
                    return (string) $path;
                }
            }

            if (method_exists($file, 'getPathname')) {
                return (string) $file->getPathname();
            }
        }

        if (is_string($file) && is_file($file)) {
            return $file;
        }

        throw new RuntimeException('ไม่สามารถอ่านไฟล์ที่อัปโหลดได้');
    }
}
