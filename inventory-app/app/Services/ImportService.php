<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Support\Database;
use App\Support\Gate;
use ZipArchive;
use SimpleXMLElement;

class ImportService
{
    protected array $requiredHeaders = ['sku', 'name', 'category', 'qty', 'cost_price', 'sale_price', 'expire_date', 'reorder_point', 'note', 'is_active'];

    public function previewHeaders(): array
    {
        return $this->requiredHeaders;
    }

    public function preview(string $path): array
    {
        $rows = $this->readFile($path);
        return $rows;
    }

    public function import(string $path, string $mode, bool $autoCategory, int $actorId): array
    {
        $rows = $this->readFile($path);
        $results = [
            'success' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($rows as $index => $row) {
            $line = $index + 2; // header + 1
            $validation = $this->validateRow($row);
            if ($validation !== true) {
                $results['errors'][] = ['line' => $line, 'message' => $validation, 'row' => $row];
                continue;
            }

            $categoryId = $this->resolveCategory($row['category'], $autoCategory);
            if (!$categoryId) {
                $results['errors'][] = ['line' => $line, 'message' => 'ไม่พบหมวดหมู่', 'row' => $row];
                continue;
            }

            $existing = Product::findBySku($row['sku']);

            if ($existing && $mode === 'skip') {
                $results['skipped']++;
                continue;
            }

            $payload = [
                'sku' => $row['sku'],
                'name' => $row['name'],
                'note' => $row['note'] ?? '',
                'category_id' => $categoryId,
                'cost_price' => (float) $row['cost_price'],
                'sale_price' => (float) $row['sale_price'],
                'expire_date' => $row['expire_date'] ?: null,
                'reorder_point' => (int) $row['reorder_point'],
                'is_active' => (int) $row['is_active'] ? 1 : 0,
                'quantity' => (int) $row['qty'],
            ];

            try {
                Database::transaction(function () use ($existing, $payload, $actorId) {
                    if ($existing) {
                        $beforeQty = (int) $existing->quantity;
                        Product::updateById($existing->id, $payload);
                        Product::updateQuantity($existing->id, $payload['quantity']);
                        $delta = $payload['quantity'] - $beforeQty;
                        if ($delta !== 0) {
                            StockMovement::record([
                                'product_id' => $existing->id,
                                'type' => 'adjust',
                                'amount' => abs($delta),
                                'note' => 'ปรับตามนำเข้าไฟล์',
                                'actor_id' => $actorId,
                                'happened_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    } else {
                        $productId = Product::create($payload);
                        if ($payload['quantity'] > 0) {
                            StockMovement::record([
                                'product_id' => $productId,
                                'type' => 'adjust',
                                'amount' => $payload['quantity'],
                                'note' => 'ตั้งต้นตามนำเข้าไฟล์',
                                'actor_id' => $actorId,
                                'happened_at' => date('Y-m-d H:i:s'),
                            ]);
                        }
                    }
                });
                $results['success']++;
            } catch (\Throwable $e) {
                $results['errors'][] = ['line' => $line, 'message' => $e->getMessage(), 'row' => $row];
            }
        }

        return $results;
    }

    protected function readFile(string $path): array
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($extension === 'csv') {
            return $this->readCsv($path);
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            return $this->readXlsx($path);
        }

        throw new \RuntimeException('รูปแบบไฟล์ไม่รองรับ');
    }

    protected function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('ไม่สามารถเปิดไฟล์ได้');
        }

        $headers = fgetcsv($handle);
        $rows = [];
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = array_combine($headers, $data);
        }
        fclose($handle);
        return $rows;
    }

    protected function readXlsx(string $path): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('ไม่สามารถเปิดไฟล์ได้');
        }

        $sharedStrings = [];
        $sheetData = '';

        $sharedIndex = $zip->locateName('xl/sharedStrings.xml');
        if ($sharedIndex !== false) {
            $xml = simplexml_load_string($zip->getFromIndex($sharedIndex));
            foreach ($xml->si as $stringItem) {
                $sharedStrings[] = (string) $stringItem->t;
            }
        }

        $sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml');
        if ($sheetIndex === false) {
            throw new \RuntimeException('ไม่พบชีตที่ 1');
        }

        $sheetXml = simplexml_load_string($zip->getFromIndex($sheetIndex));
        $sheetXml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        $headers = [];
        foreach ($sheetXml->sheetData->row as $row) {
            $rowValues = [];
            foreach ($row->c as $cell) {
                $value = '';
                $type = (string) ($cell['t'] ?? '');
                if ($type === 's') {
                    $index = (int) $cell->v;
                    $value = $sharedStrings[$index] ?? '';
                } else {
                    $value = (string) $cell->v;
                }
                $rowValues[] = $value;
            }

            if (empty($headers)) {
                $headers = $rowValues;
                continue;
            }

            if (!array_filter($rowValues)) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($rowValues, count($headers), ''));
        }

        $zip->close();
        return $rows;
    }

    protected function validateRow(array $row): bool|string
    {
        foreach ($this->requiredHeaders as $header) {
            if (!array_key_exists($header, $row)) {
                return 'รูปแบบคอลัมน์ไม่ถูกต้อง';
            }
        }

        if ($row['sku'] === '') {
            return 'SKU ต้องไม่ว่าง';
        }
        if ($row['name'] === '') {
            return 'ชื่อสินค้าต้องไม่ว่าง';
        }
        if (!is_numeric($row['qty']) || (int) $row['qty'] < 0) {
            return 'จำนวนต้องไม่ติดลบ';
        }
        if (!is_numeric($row['cost_price']) || !is_numeric($row['sale_price'])) {
            return 'ราคาต้องเป็นตัวเลข';
        }
        if ($row['expire_date'] && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $row['expire_date'])) {
            return 'รูปแบบวันหมดอายุไม่ถูกต้อง';
        }
        if (!is_numeric($row['reorder_point']) || (int) $row['reorder_point'] < 0) {
            return 'จุดสั่งซื้อซ้ำต้องไม่ติดลบ';
        }

        return true;
    }

    protected function resolveCategory(string $name, bool $autoCreate): ?int
    {
        $stmt = db()->prepare('SELECT id FROM categories WHERE name = :name LIMIT 1');
        $stmt->execute(['name' => $name]);
        $id = $stmt->fetchColumn();
        if ($id) {
            return (int) $id;
        }

        if (!$autoCreate) {
            return null;
        }

        Category::create([
            'name' => $name,
            'note' => 'สร้างอัตโนมัติจากการนำเข้า',
            'is_active' => 1,
        ]);
        return (int) db()->lastInsertId();
    }
}
