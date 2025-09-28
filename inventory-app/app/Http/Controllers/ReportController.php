<?php
namespace App\Http\Controllers;

use App\Models\Product;
use DateInterval;
use DateTime;

class ReportController extends Controller
{
    public function expiring(): void
    {
        $days = (int) ($_GET['days'] ?? 30);
        $today = new DateTime('now', new \DateTimeZone('UTC'));
        $products = array_filter(Product::withCategory(), function ($product) use ($days, $today) {
            if (!$product['expire_date']) {
                return false;
            }
            $expire = new DateTime($product['expire_date'], new \DateTimeZone('UTC'));
            if ($expire < $today) {
                return false;
            }
            $diff = $today->diff($expire)->days;
            return $diff <= $days;
        });

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv('expiring_'.$days.'.csv', $products);
            return;
        }

        view('reports/expiring', ['products' => $products, 'days' => $days]);
    }

    public function lowStock(): void
    {
        $products = array_filter(Product::withCategory(), function ($product) {
            return (int) $product['reorder_point'] >= 0 && (int) $product['quantity'] <= (int) $product['reorder_point'];
        });

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv('low_stock.csv', $products);
            return;
        }

        view('reports/low_stock', ['products' => $products]);
    }

    public function stockValue(): void
    {
        $products = Product::withCategory();
        $total = 0;
        foreach ($products as $product) {
            $total += (float) $product['cost_price'] * (int) $product['quantity'];
        }

        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->exportCsv('stock_value.csv', $products, true);
            return;
        }

        view('reports/stock_value', ['products' => $products, 'total' => $total]);
    }

    protected function exportCsv(string $filename, array $products, bool $withValue = false): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output = fopen('php://output', 'w');
        $headers = ['SKU', 'ชื่อสินค้า', 'หมวดหมู่', 'คงเหลือ', 'จุดสั่งซื้อซ้ำ', 'วันหมดอายุ'];
        if ($withValue) {
            $headers[] = 'ราคาทุน';
            $headers[] = 'มูลค่าคงเหลือ';
        }
        fputcsv($output, $headers);
        foreach ($products as $product) {
            $row = [
                $product['sku'],
                $product['name'],
                $product['category_name'] ?? '',
                $product['quantity'],
                $product['reorder_point'],
                $product['expire_date'],
            ];
            if ($withValue) {
                $row[] = $product['cost_price'];
                $row[] = (float) $product['cost_price'] * (int) $product['quantity'];
            }
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
