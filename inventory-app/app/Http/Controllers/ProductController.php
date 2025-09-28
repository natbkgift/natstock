<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(protected ProductService $service = new ProductService())
    {
    }

    public function index(): void
    {
        $keyword = trim($_GET['keyword'] ?? '');
        $page = (int) ($_GET['page'] ?? 1);
        $perPage = 10;

        $products = $keyword ? Product::search($keyword) : Product::withCategory();
        $pagination = paginate($products, $perPage, $page);

        view('products/index', [
            'products' => $pagination['data'],
            'keyword' => $keyword,
            'pagination' => $pagination,
        ]);
    }

    public function create(): void
    {
        $categories = Category::active();
        view('products/create', compact('categories'));
    }

    public function store(): void
    {
        verify_csrf();
        $data = $this->prepareData();
        $errors = $this->validateRules($data);
        if ($errors) {
            flash('error', 'กรุณาแก้ไขข้อผิดพลาด');
            redirect('/admin/products/create');
        }

        $this->service->create($data);
        flash('success', 'เพิ่มสินค้าสำเร็จ');
        redirect('/admin/products');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $product = Product::find($id);
        if (!$product) {
            redirect('/admin/products');
        }
        $categories = Category::active();
        view('products/edit', compact('product', 'categories'));
    }

    public function update(): void
    {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $data = $this->prepareData();
        $errors = $this->validateRules($data);
        if ($errors) {
            flash('error', 'กรุณาแก้ไขข้อผิดพลาด');
            redirect('/admin/products/edit?id='.$id);
        }

        $this->service->update($id, $data);
        flash('success', 'ปรับปรุงสินค้าสำเร็จ');
        redirect('/admin/products');
    }

    public function destroy(): void
    {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $this->service->delete($id);
        flash('success', 'ลบสินค้าเรียบร้อย');
        redirect('/admin/products');
    }

    protected function prepareData(): array
    {
        return [
            'sku' => trim($_POST['sku'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
            'category_id' => (int) ($_POST['category_id'] ?? 0),
            'cost_price' => (float) ($_POST['cost_price'] ?? 0),
            'sale_price' => (float) ($_POST['sale_price'] ?? 0),
            'expire_date' => $_POST['expire_date'] ?? null,
            'reorder_point' => (int) ($_POST['reorder_point'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'quantity' => (int) ($_POST['quantity'] ?? 0),
        ];
    }

    protected function validateRules(array $data): array
    {
        return $this->validate([
            'sku' => 'required',
            'name' => 'required',
            'category_id' => 'integer|min:1',
            'cost_price' => 'numeric',
            'sale_price' => 'numeric',
            'reorder_point' => 'integer|min:0',
            'quantity' => 'integer|min:0',
            'expire_date' => 'date',
        ], $data);
    }
}
