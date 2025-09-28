<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use DateInterval;
use DateTime;

class DashboardController extends Controller
{
    public function index(): void
    {
        $products = Product::withCategory();
        $today = new DateTime('now', new \DateTimeZone('UTC'));

        $expiringCounts = [30 => 0, 60 => 0, 90 => 0];
        $lowStock = 0;
        $totalValue = 0.0;

        foreach ($products as $product) {
            $totalValue += (float) $product['cost_price'] * (int) $product['quantity'];

            if ($product['reorder_point'] !== null && (int) $product['quantity'] <= (int) $product['reorder_point']) {
                $lowStock++;
            }

            if ($product['expire_date']) {
                $expireDate = new DateTime($product['expire_date'], new \DateTimeZone('UTC'));
                $diff = $today->diff($expireDate)->days;
                if ($expireDate >= $today) {
                    foreach ([30, 60, 90] as $days) {
                        if ($diff <= $days) {
                            $expiringCounts[$days]++;
                        }
                    }
                }
            }
        }

        $movements = StockMovement::latest();

        view('dashboard/index', [
            'expiringCounts' => $expiringCounts,
            'lowStock' => $lowStock,
            'totalValue' => $totalValue,
            'movements' => $movements,
        ]);
    }
}
