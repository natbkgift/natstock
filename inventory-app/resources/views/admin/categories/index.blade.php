@extends('layouts.admin')

@section('title', 'หมวดหมู่สินค้า')
@section('page_title', 'หมวดหมู่สินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">หมวดหมู่สินค้า</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการหมวดหมู่</h3>
        @can('create', App\Models\Category::class)
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> เพิ่มหมวดหมู่</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.categories.index') }}" class="mb-3">
            <div class="form-row">
                <div class="col-md-6 mb-2">
                    <label for="search" class="sr-only">ค้นหาชื่อหมวดหมู่</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="ค้นหาชื่อหมวดหมู่" value="{{ $filters['search'] }}">
                </div>
                <div class="col-md-4 mb-2">
                    <label for="status" class="sr-only">สถานะ</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">ทุกสถานะ</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2 text-right">
                    <button type="submit" class="btn btn-outline-primary btn-block">ค้นหา</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ชื่อหมวดหมู่</th>
                        <th>สถานะ</th>
                        <th class="text-center">จำนวนสินค้า</th>
                        <th>อัปเดตล่าสุด</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>
                                <span class="badge badge-{{ $category->is_active ? 'success' : 'secondary' }}">{{ $category->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}</span>
                            </td>
                            <td class="text-center">{{ number_format($category->products_count) }}</td>
                            <td>{{ optional($category->updated_at)->format('d/m/Y H:i') }}</td>
                            <td class="text-right">
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('update', $category)
                                        <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-outline-secondary">แก้ไข</a>
                                    @endcan
                                    @can('delete', $category)
                                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('ยืนยันการลบหมวดหมู่นี้หรือไม่?');" style="display: contents;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger">ลบ</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">ยังไม่มีหมวดหมู่ในระบบ</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $categories->links() }}
        </div>
    </div>
</div>
@endsection
