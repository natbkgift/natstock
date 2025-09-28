@extends('layouts.admin')

@section('title', 'รายงานสต็อกต่ำ')
@section('page_title', 'รายงานสต็อกต่ำ')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">รายงาน</a></li>
    <li class="breadcrumb-item active">รายงานสต็อกต่ำ</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการสินค้าที่ปริมาณใกล้หมด</h3>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="fas fa-file-csv mr-1"></i> ส่งออก CSV
        </a>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center">
                @foreach($summary as $item)
                    <span class="badge badge-light border mr-2 mb-2">{{ $item }}</span>
                @endforeach
            </div>
        </div>
        <form method="GET" class="mb-4">
            @include('admin.reports.partials.common-filters')
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
                        <th>หมวดหมู่</th>
                        <th class="text-right">คงเหลือ (qty)</th>
                        <th class="text-right">จุดสั่งซื้อซ้ำ</th>
                        <th>วันหมดอายุ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $expireDate = $product->expire_date instanceof \Carbon\Carbon
                                ? $product->expire_date->format('Y-m-d')
                                : ($product->expire_date ?: '-');
                        @endphp
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td class="text-right">
                                {{ number_format((int) $product->qty) }}
                                <span class="badge badge-danger ml-2">สต็อกต่ำ</span>
                            </td>
                            <td class="text-right">{{ number_format((int) $product->reorder_point) }}</td>
                            <td>{{ $expireDate ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $product->is_active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $product->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">ไม่มีข้อมูลตามตัวกรองที่เลือก</td>
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
