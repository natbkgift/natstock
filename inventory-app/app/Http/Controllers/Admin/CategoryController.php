<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Category::class);

        $search = trim((string) $request->string('search')->toString());
        $status = $request->string('status')->toString();

        $categoriesQuery = Category::query()
            ->withCount('products')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name');

        /** @var LengthAwarePaginator $categories */
        $categories = $categoriesQuery->paginate(15)->appends($request->query());

        return view('admin.categories.index', [
            'categories' => $categories,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function edit(Category $category): View
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $data = $request->validated();
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'บันทึกข้อมูลเรียบร้อย');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('warning', 'ไม่สามารถลบหมวดหมู่ที่มีสินค้าอยู่ได้');
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', 'ลบข้อมูลเรียบร้อย');
    }
}
