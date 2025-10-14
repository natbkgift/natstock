<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CsvPreviewService
{
    private const REQUIRED_HEADERS = ['sku', 'qty'];

    private const HEADER_ALIASES = [
        'sku' => 'sku',
        'sku_code' => 'sku',
        'product_sku' => 'sku',
        'qty' => 'qty',
        'quantity' => 'qty',
        'จำนวน' => 'qty',
        'lot' => 'lot_no',
        'lot_no' => 'lot_no',
        'lot number' => 'lot_no',
        'lot_number' => 'lot_no',
        'expire_date' => 'expire_date',
        'expiry_date' => 'expire_date',
        'expired_at' => 'expire_date',
        'name' => 'name',
        'product_name' => 'name',
        'category' => 'category',
        'category_name' => 'category',
        'reorder_point' => 'reorder_point',
        'reorder' => 'reorder_point',
        'min_qty' => 'reorder_point',
        'note' => 'note',
        'remark' => 'note',
        'is_active' => 'is_active',
        'active' => 'is_active',
        'enabled' => 'is_active',
        'cost_price' => 'cost_price',
        'cost' => 'cost_price',
        'ต้นทุน' => 'cost_price',
        'sale_price' => 'sale_price',
        'price' => 'sale_price',
        'ราคาขาย' => 'sale_price',
    ];

    private const IGNORED_HEADERS = ['cost_price', 'sale_price'];

    /**
     * @return array{headers: array<int, string>, rows: array<int, array{row_number: int, cells: array<string, string>, errors: array<int, string>}>, total_rows: int, ignored_columns: array<int, string>}
     */
    public function preview(UploadedFile $csv): array
    {
        $handle = fopen($csv->getRealPath(), 'rb');
        if ($handle === false) {
            throw new \RuntimeException('ไม่สามารถเปิดไฟล์ CSV ได้');
        }

        $headers = $this->readRow($handle);
        if ($headers === null) {
            fclose($handle);
            throw new \RuntimeException('ไฟล์ CSV ไม่มีข้อมูลส่วนหัว');
        }

        $normalized = $this->normalizeHeaders($headers);
        $ignoredColumns = $this->collectIgnoredColumns($normalized, $headers);

        $rows = [];
        $totalRows = 0;
        $rowNumber = 1; // starts after header

        while (($row = $this->readRow($handle)) !== null) {
            $rowNumber++;
            if ($this->isRowEmpty($row)) {
                continue;
            }

            $totalRows++;
            $cells = $this->mapRowToHeaders($row, $normalized);
            $errors = $this->validateRow($cells, $rowNumber);

            if (count($rows) < 20) {
                $rows[] = [
                    'row_number' => $rowNumber,
                    'cells' => $cells,
                    'errors' => $errors,
                ];
            }
        }

        fclose($handle);

        $this->assertRequiredHeadersPresent($normalized);

        $headers = array_values(array_filter(array_unique($normalized)));

        return [
            'headers' => $headers,
            'rows' => $rows,
            'total_rows' => $totalRows,
            'ignored_columns' => $ignoredColumns,
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function readRow($handle): ?array
    {
        $row = fgetcsv($handle);
        if ($row === false) {
            return null;
        }

        if ($row !== null) {
            $row = array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row);
        }

        return $row;
    }

    /**
     * @param  array<int, string>  $headers
     * @return array<int, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];
        foreach ($headers as $header) {
            $key = $this->normalizeHeaderName($header);
            $normalized[] = $key;
        }

        return $normalized;
    }

    private function normalizeHeaderName(?string $header): string
    {
        $header = $header ?? '';
        $header = preg_replace('/^[\xEF\xBB\xBF]/', '', $header) ?? '';
        $header = trim($header);
        $lower = Str::of($header)->lower()->replace(['-', ' '], '_')->toString();

        return self::HEADER_ALIASES[$lower] ?? $lower;
    }

    /**
     * @param  array<int, string>  $normalized
     * @param  array<int, string>  $original
     * @return array<int, string>
     */
    private function collectIgnoredColumns(array $normalized, array $original): array
    {
        $ignored = [];
        foreach ($normalized as $index => $name) {
            if (in_array($name, self::IGNORED_HEADERS, true)) {
                $ignored[] = $original[$index] ?? $name;
            }
        }

        return array_values(array_unique(array_filter($ignored)));
    }

    /**
     * @param  array<int, string>  $row
     * @param  array<int, string>  $normalizedHeaders
     * @return array<string, string>
     */
    private function mapRowToHeaders(array $row, array $normalizedHeaders): array
    {
        $cells = [];
        foreach ($normalizedHeaders as $index => $header) {
            if ($header === '') {
                continue;
            }
            $cells[$header] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $cells;
    }

    /**
     * @param  array<string, string>  $cells
     * @return array<int, string>
     */
    private function validateRow(array $cells, int $rowNumber): array
    {
        $errors = [];

        foreach (self::REQUIRED_HEADERS as $required) {
            if (($cells[$required] ?? '') === '') {
                $errors[] = sprintf('แถวที่ %d: จำเป็นต้องระบุค่า %s', $rowNumber, $this->headerLabel($required));
            }
        }

        if (isset($cells['qty']) && $cells['qty'] !== '') {
            if (!ctype_digit(ltrim($cells['qty'], '+'))) {
                $errors[] = sprintf('แถวที่ %d: จำนวนต้องเป็นจำนวนเต็มที่มากกว่าหรือเท่ากับ 0', $rowNumber);
            } else {
                $qty = (int) $cells['qty'];
                if ($qty < 0) {
                    $errors[] = sprintf('แถวที่ %d: จำนวนต้องเป็นจำนวนเต็มที่มากกว่าหรือเท่ากับ 0', $rowNumber);
                }
            }
        }

        if (isset($cells['reorder_point']) && $cells['reorder_point'] !== '') {
            if (!ctype_digit(ltrim($cells['reorder_point'], '+'))) {
                $errors[] = sprintf('แถวที่ %d: จุดสั่งซื้อซ้ำต้องเป็นจำนวนเต็มที่มากกว่าหรือเท่ากับ 0', $rowNumber);
            }
        }

        if (isset($cells['expire_date']) && $cells['expire_date'] !== '') {
            $date = \DateTime::createFromFormat('Y-m-d', $cells['expire_date']);
            $valid = $date && $date->format('Y-m-d') === $cells['expire_date'];
            if (!$valid) {
                $errors[] = sprintf('แถวที่ %d: รูปแบบวันหมดอายุต้องเป็น YYYY-MM-DD', $rowNumber);
            }
        }

        if (isset($cells['lot_no']) && $cells['lot_no'] !== '' && mb_strlen($cells['lot_no']) > 16) {
            $errors[] = sprintf('แถวที่ %d: หมายเลขล็อตต้องมีความยาวไม่เกิน 16 ตัวอักษร', $rowNumber);
        }

        return $errors;
    }

    /**
     * @param  array<int, string>  $row
     */
    private function isRowEmpty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $normalized
     */
    private function assertRequiredHeadersPresent(array $normalized): void
    {
        $present = array_filter(array_unique($normalized));
        foreach (self::REQUIRED_HEADERS as $required) {
            if (!in_array($required, $present, true)) {
                throw new \RuntimeException(sprintf('ไม่พบคอลัมน์ %s ในไฟล์ CSV', $this->headerLabel($required)));
            }
        }
    }

    private function headerLabel(string $key): string
    {
        return match ($key) {
            'sku' => 'SKU',
            'qty' => 'จำนวน',
            'lot_no' => 'หมายเลขล็อต',
            'expire_date' => 'วันหมดอายุ',
            'name' => 'ชื่อสินค้า',
            'category' => 'หมวดหมู่',
            'reorder_point' => 'จุดสั่งซื้อซ้ำ',
            'note' => 'หมายเหตุ',
            'is_active' => 'สถานะใช้งาน',
            default => Str::of($key)->replace('_', ' ')->title()->toString(),
        };
    }
}
