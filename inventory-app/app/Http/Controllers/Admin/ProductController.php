<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\AuditLogger;
use App\Support\PriceGuard;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private readonly AuditLogger $auditLogger)
    {
    }

    public function ajaxCreateCategory(Request $request)
    {
        $this->authorize('create', Product::class);
        $name = trim($request->input('name'));
        if ($name === '') {
            return response()->json(['error' => 'กรุณากรอกชื่อหมวดหมู่'], 422);
        }
        $category = Category::firstOrCreate(
            ['name' => $name],
            ['is_active' => true]
        );
        return response()->json(['id' => $category->id, 'name' => $category->name]);
    }

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Product::class);

        $search = trim((string) $request->string('search')->toString());
        $categoryId = $request->integer('category_id');
        $status = $request->string('status')->toString();
        $expiring = $request->integer('expiring');
        $lowStock = $request->boolean('low_stock');

        $productsQuery = Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('sku', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($categoryId > 0, fn ($query) => $query->where('category_id', $categoryId))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(in_array($expiring, [30, 60, 90], true), fn ($query) => $query->expiringIn($expiring))
            ->when($lowStock, fn ($query) => $query->lowStock())
            ->orderBy('sku');

        /** @var LengthAwarePaginator $products */
        $products = $productsQuery->paginate(15)->appends($request->query());

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.products.index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'category_id' => $categoryId,
                'status' => $status,
                'expiring' => in_array($expiring, [30, 60, 90], true) ? $expiring : null,
                'low_stock' => $lowStock,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Product::class);

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.products.create', compact('categories'));
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $validated = $request->validated();
        PriceGuard::strip($validated);
        $product = DB::transaction(function () use ($validated) {
            $data = $this->formatProductData($validated);
            $data['category_id'] = $this->resolveCategoryId($validated);

            return Product::create($data);
        });

        $this->auditLogger->log(
            'product.created',
            'สร้างสินค้าใหม่',
            [
                'sku' => $product->sku,
                'name' => $product->name,
                'qty' => $product->qty,
            ],
            $product,
            $request->user(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'บันทึกข้อมูลสินค้าเรียบร้อย');
    }

    public function show(Product $product): View
    {
        $this->authorize('view', $product);
        return view('admin.products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $this->authorize('update', $product);

        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);
        $validated = $request->validated();
        PriceGuard::strip($validated);
        $before = Arr::only($product->toArray(), ['sku', 'name', 'qty', 'reorder_point', 'is_active']);

        DB::transaction(function () use (&$product, $validated) {
            $data = $this->formatProductData($validated);
            $data['category_id'] = $this->resolveCategoryId($validated, $product->category_id);

            $product->update($data);
        });

        $this->auditLogger->log(
            'product.updated',
            'แก้ไขข้อมูลสินค้า',
            [
                'before' => $before,
                'after' => Arr::only($product->fresh()->toArray(), ['sku', 'name', 'qty', 'reorder_point', 'is_active']),
            ],
            $product,
            $request->user(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'บันทึกข้อมูลสินค้าเรียบร้อย');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($product->stockMovements()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('warning', 'ไม่สามารถลบสินค้าที่มีประวัติการเคลื่อนไหวได้');
        }

        $details = ['sku' => $product->sku, 'name' => $product->name];
        $product->delete();

        $this->auditLogger->log(
            'product.deleted',
            'ลบสินค้าออกจากระบบ',
            $details,
            $product,
            request()->user(),
        );

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'ลบข้อมูลเรียบร้อย');
    }

    private function formatProductData(array $data): array
    {
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['expire_date'] = isset($data['expire_date']) && $data['expire_date'] !== ''
            ? $data['expire_date']
            : null;
        $data['qty'] = (int) ($data['qty'] ?? 0);
        $data['reorder_point'] = (int) ($data['reorder_point'] ?? 0);

        if (array_key_exists('category_id', $data) && $data['category_id'] === '') {
            $data['category_id'] = null;
        }

        // Normalize pricing values
        if (array_key_exists('cost_price', $data)) {
            $data['cost_price'] = (float) ($data['cost_price'] ?? 0);
        }
        if (array_key_exists('sale_price', $data)) {
            $sale = $data['sale_price'];
            $data['sale_price'] = ($sale === null || $sale === '') ? 0 : (float) $sale;
        }

        if (array_key_exists('sku', $data) && trim((string) $data['sku']) === '') {
            unset($data['sku']);
        }

        unset($data['new_category_name'], $data['new_category']);

        return $data;
    }

    private function resolveCategoryId(array $data, ?int $current = null): int
    {
        $newCategoryName = trim((string) ($data['new_category_name'] ?? ''));

        if ($newCategoryName !== '') {
            $category = Category::query()->firstOrCreate(
                ['name' => $newCategoryName],
                ['is_active' => true]
            );

            return $category->id;
        }

        $categoryId = $data['category_id'] ?? $current;

        if ($categoryId === null || $categoryId === '') {
            return $this->defaultCategory()->id;
        }

        return (int) $categoryId;
    }

    private function defaultCategory(): Category
    {
        return Category::query()->firstOrCreate(
            ['name' => 'ไม่ระบุหมวดหมู่'],
            ['is_active' => true]
        );
    }
}
