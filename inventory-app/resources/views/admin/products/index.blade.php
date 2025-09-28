@extends('layouts.admin')

@section('title', 'จัดการสินค้า')
@section('page_title', 'จัดการสินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">สินค้า</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
@endpush

@section('content')
<div class="card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการสินค้าในระบบ</h3>
        @can('create', App\Models\Product::class)
            <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> เพิ่มสินค้า</a>
        @endcan
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.products.index') }}" class="mb-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="search">ค้นหา SKU/ชื่อสินค้า</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="ระบุรหัสหรือชื่อสินค้า" value="{{ $filters['search'] }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="category_id">หมวดหมู่</label>
                    <select name="category_id" id="category_id" class="form-control select2" data-placeholder="เลือกหมวดหมู่">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) $filters['category_id'] === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="status">สถานะ</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <option value="active" {{ $filters['status'] === 'active' ? 'selected' : '' }}>ใช้งาน</option>
                        <option value="inactive" {{ $filters['status'] === 'inactive' ? 'selected' : '' }}>ปิดใช้งาน</option>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label for="expiring">ใกล้หมดอายุ</label>
                    <select name="expiring" id="expiring" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <option value="30" {{ $filters['expiring'] === 30 ? 'selected' : '' }}>ภายใน 30 วัน</option>
                        <option value="60" {{ $filters['expiring'] === 60 ? 'selected' : '' }}>ภายใน 60 วัน</option>
                        <option value="90" {{ $filters['expiring'] === 90 ? 'selected' : '' }}>ภายใน 90 วัน</option>
                    </select>
                </div>
                <div class="form-group col-md-1 text-center">
                    <div class="custom-control custom-switch mt-4">
                        <input type="checkbox" class="custom-control-input" id="low_stock" name="low_stock" value="1" {{ $filters['low_stock'] ? 'checked' : '' }}>
                        <label class="custom-control-label" for="low_stock">สต็อกต่ำ</label>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary mr-2">ค้นหา</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">ล้างตัวกรอง</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>ชื่อสินค้า</th>
                        <th>หมวดหมู่</th>
                        <th>วันหมดอายุ</th>
                        <th class="text-right">คงเหลือ</th>
                        <th class="text-right">จุดสั่งซื้อซ้ำ</th>
                        <th>สถานะ</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>
                                <div>{{ $product->name }}</div>
                                @if($product->qty <= $product->reorder_point)
                                    <span class="badge badge-danger">สต็อกต่ำ</span>
                                @endif
                            </td>
                            <td>{{ optional($product->category)->name ?? '-' }}</td>
                            <td>
                                @if($product->expire_date)
                                    @php
                                        $diff = now()->diffInDays($product->expire_date, false);
                                    @endphp
                                    @if($diff < 0)
                                        <span class="badge badge-danger">หมดอายุแล้ว ({{ $product->expire_date->format('d/m/Y') }})</span>
                                    @elseif($diff === 0)
                                        <span class="badge badge-danger">หมดอายุวันนี้</span>
                                    @elseif($diff <= 30)
                                        <span class="badge badge-warning">ใกล้หมดอายุใน {{ $diff }} วัน</span>
                                    @elseif($diff <= 60)
                                        <span class="badge badge-info">จะหมดอายุใน {{ $diff }} วัน</span>
                                    @else
                                        <span class="badge badge-success">หมดอายุ {{ $product->expire_date->format('d/m/Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-right">{{ number_format($product->qty) }}</td>
                            <td class="text-right">{{ number_format($product->reorder_point) }}</td>
                            <td>
                                <span class="badge badge-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}</span>
                            </td>
                            <td class="text-right">
                                <div class="btn-group btn-group-sm" role="group">
                                    @can('update', $product)
                                        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-secondary">แก้ไข</a>
                                    @endcan
                                    @can('delete', $product)
                                        <form action="{{ route('admin.products.destroy', $product) }}" method="POST" onsubmit="return confirm('ยืนยันการลบสินค้านี้หรือไม่?');" style="display: contents;">
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
                            <td colspan="8" class="text-center text-muted">ยังไม่มีข้อมูลสินค้า</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $products->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            language: {
                noResults: () => 'ไม่พบข้อมูล',
                searching: () => 'กำลังค้นหา...'
            }
        });
    });
</script>
@endpush
