@extends('layouts.admin')

@section('title', 'รายงานสต็อกต่ำ')
@section('page_title', 'รายงานสินค้าสต็อกต่ำ')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">รายงาน</a></li>
    <li class="breadcrumb-item active">สินค้าสต็อกต่ำ</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการสินค้าที่ปริมาณคงเหลือรวมต่ำกว่าจุดสั่งซื้อซ้ำ</h3>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="fas fa-file-csv mr-1"></i> ส่งออก CSV
        </a>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-4">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label for="category_id">หมวดหมู่</label>
                    <select name="category_id" id="category_id" class="form-control">
                        <option value="">ทั้งหมด</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) ($filters['category_id'] ?? 0) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-8">
                    <label for="search">ค้นหา SKU/ชื่อสินค้า</label>
                    <input type="text" name="search" id="search" value="{{ $filters['search'] ?? '' }}" class="form-control" placeholder="เช่น SKU หรือชื่อสินค้า">
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                <a href="{{ route('admin.reports.low-stock') }}" class="btn btn-secondary">ล้างตัวกรอง</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>SKU</th>
                        <th>ชื่อสินค้า</th>
                        <th class="text-right">คงเหลือรวม</th>
                        <th class="text-right">จุดสั่งซื้อซ้ำ</th>
                        <th>หมวดหมู่</th>
                        <th>ล็อตเด่น (สรุปย่อย)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td class="text-right">{{ number_format((int) ($product->qty_total ?? $product->qtyCurrent())) }}</td>
                            <td class="text-right">{{ number_format((int) $product->reorder_point) }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>
                                @if($product->batches->isNotEmpty())
                                    <ul class="list-unstyled mb-0">
                                        @foreach($product->batches as $batch)
                                            <li>
                                                <strong>{{ $batch->sub_sku ?? '-' }}</strong>
                                                <span class="text-muted">- {{ number_format((int) $batch->qty) }} ชิ้น</span>
                                                <small class="text-muted">(หมดอายุ {{ $batch->expire_date?->format('d/m/Y') ?? '-' }})</small>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">ไม่มีข้อมูลล็อต (ใช้ยอดคงเหลือเดิม)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">ไม่มีข้อมูลสินค้าสต็อกต่ำตามตัวกรองที่เลือก</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(method_exists($products, 'links'))
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
