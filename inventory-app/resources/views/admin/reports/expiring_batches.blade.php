@extends('layouts.admin')

@section('title', 'รายงานล็อตใกล้หมดอายุ')
@section('page_title', 'รายงานล็อตใกล้หมดอายุ')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">รายงาน</a></li>
    <li class="breadcrumb-item active">ล็อตใกล้หมดอายุ</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการล็อตที่ใกล้หมดอายุ</h3>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="fas fa-file-csv mr-1"></i> ส่งออก CSV
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="days">ภายในกี่วัน</label>
                    <select name="days" id="days" class="form-control">
                        @foreach($dayOptions as $option)
                            <option value="{{ $option }}" @selected((int) ($filters['days'] ?? 30) === $option)>ภายใน {{ $option }} วัน</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label for="category_id">หมวดหมู่</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="search">ค้นหา SKU/ชื่อสินค้า/Sub-SKU</label>
                    <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="เช่น SKU หรือชื่อสินค้า">
                </div>
                <div class="form-group col-md-2 d-flex align-items-end">
                    <div class="custom-control custom-switch">
                        <input type="hidden" name="active" value="0">
                        <input type="checkbox" class="custom-control-input" id="active" name="active" value="1" {{ ($filters['active_only'] ?? true) ? 'checked' : '' }}>
                        <label class="custom-control-label" for="active">เฉพาะล็อตที่เปิดใช้งาน</label>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                <a href="{{ route('admin.reports.expiring-batches') }}" class="btn btn-secondary">ล้างตัวกรอง</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>SKU</th>
                        <th>ชื่อสินค้า</th>
                        <th>Sub-SKU/ล็อต</th>
                        <th>วันหมดอายุ</th>
                        <th class="text-right">คงเหลือ</th>
                        <th>สถานะล็อต</th>
                        <th>หมวดหมู่</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>{{ $batch->product?->sku ?? '-' }}</td>
                            <td>{{ $batch->product?->name ?? '-' }}</td>
                            <td>{{ $batch->sub_sku ?? '-' }}</td>
                            <td>{{ $batch->expire_date?->format('Y-m-d') ?? '-' }}</td>
                            <td class="text-right">{{ number_format((int) $batch->qty) }}</td>
                            <td>
                                <span class="badge {{ $batch->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $batch->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                            </td>
                            <td>{{ $batch->product?->category?->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">ไม่มีข้อมูลล็อตตามตัวกรองที่เลือก</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($batches, 'links'))
            <div class="mt-3">
                {{ $batches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
