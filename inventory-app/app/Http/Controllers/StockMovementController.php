<?php
namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;

class StockMovementController extends Controller
{
    public function __construct(protected StockService $service = new StockService())
    {
    }

    public function index(): void
    {
        $movements = StockMovement::latest(100);
        view('movements/index', compact('movements'));
    }

    public function create(): void
    {
        $products = Product::withCategory();
        view('movements/create', compact('products'));
    }

    public function store(): void
    {
        verify_csrf();
        $data = [
            'product_id' => (int) ($_POST['product_id'] ?? 0),
            'type' => $_POST['type'] ?? 'in',
            'amount' => (int) ($_POST['amount'] ?? 0),
            'note' => trim($_POST['note'] ?? ''),
            'happened_at' => $_POST['happened_at'] ?? null,
        ];

        $errors = $this->validate([
            'product_id' => 'integer|min:1',
            'type' => 'required',
            'amount' => 'integer|min:0',
        ], $data);

        if ($errors) {
            flash('error', 'กรุณาแก้ไขข้อผิดพลาด');
            redirect('/admin/movements/create');
        }

        $actor = auth()->user();
        $this->service->move($data['product_id'], $data['type'], $data['amount'], $data['note'], $actor->id, $data['happened_at']);
        flash('success', 'บันทึกการเคลื่อนไหวเรียบร้อย');
        redirect('/admin/movements');
    }
}
