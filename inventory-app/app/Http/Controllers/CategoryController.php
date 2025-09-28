<?php
namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Gate;

class CategoryController extends Controller
{
    public function index(): void
    {
        $categories = Category::all();
        view('categories/index', compact('categories'));
    }

    public function create(): void
    {
        view('categories/create');
    }

    public function store(): void
    {
        verify_csrf();
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];

        $errors = $this->validate([
            'name' => 'required',
        ], $data);

        if ($errors) {
            flash('error', 'กรุณาแก้ไขข้อผิดพลาด');
            redirect('/admin/categories/create');
        }

        Category::create($data);
        flash('success', 'บันทึกหมวดหมู่สำเร็จ');
        redirect('/admin/categories');
    }

    public function edit(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $category = Category::find($id);
        if (!$category) {
            redirect('/admin/categories');
        }
        view('categories/edit', compact('category'));
    }

    public function update(): void
    {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        $errors = $this->validate(['name' => 'required'], $data);
        if ($errors) {
            flash('error', 'กรุณาแก้ไขข้อผิดพลาด');
            redirect('/admin/categories/edit?id='.$id);
        }

        Category::updateById($id, $data);
        flash('success', 'ปรับปรุงหมวดหมู่สำเร็จ');
        redirect('/admin/categories');
    }

    public function destroy(): void
    {
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        Category::deleteById($id);
        flash('success', 'ลบหมวดหมู่เรียบร้อย');
        redirect('/admin/categories');
    }
}
