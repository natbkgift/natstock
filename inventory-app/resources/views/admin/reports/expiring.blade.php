@extends('layouts.admin')

@section('title', 'รายงานใกล้หมดอายุ')
@section('page_title', 'รายงานใกล้หมดอายุ')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">รายงาน</a></li>
    <li class="breadcrumb-item active">รายงานใกล้หมดอายุ</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการสินค้าที่ใกล้หมดอายุ</h3>
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
            <div class="form-row">
                <div class="form-group col-md-3">
                    <label for="days">ภายในกี่วัน</label>
                    <select name="days" id="days" class="form-control">
                        @foreach([30, 60, 90] as $day)
                            <option value="{{ $day }}" @selected((int) ($filters['days'] ?? 30) === $day)>ภายใน {{ $day }} วัน</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-9">
                    @include('admin.reports.partials.common-filters')
                </div>
            </div>
            <div class="text-right">
                <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                <a href="{{ route('admin.reports.expiring') }}" class="btn btn-secondary">ล้างตัวกรอง</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>SKU</th>
                        <th>ชื่อสินค้า</th>
                        <th>หมวดหมู่</th>
                        <th>วันหมดอายุ</th>
                        <th class="text-right">คงเหลือ (qty)</th>
                        <th class="text-right">จุดสั่งซื้อซ้ำ</th>
                        <th>สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $expireDate = $product->expire_date instanceof \Carbon\Carbon
                                ? $product->expire_date
                                : ($product->expire_date ? \Carbon\Carbon::parse($product->expire_date) : null);
                            $daysRemaining = isset($product->days_remaining)
                                ? max(0, (int) $product->days_remaining)
                                : ($expireDate ? max(0, \Carbon\Carbon::today()->diffInDays($expireDate, false)) : null);
                        @endphp
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td>
                                {{ $expireDate?->format('Y-m-d') ?? '-' }}
                                @if($daysRemaining !== null)
                                    @if($daysRemaining <= 7)
                                        <span class="badge badge-danger ml-2">เร่งด่วน</span>
                                    @elseif($daysRemaining <= 30)
                                        <span class="badge badge-warning ml-2">ใกล้หมดอายุ</span>
                                    @endif
                                @endif
                            </td>
                            <td class="text-right">{{ number_format((int) $product->qty) }}</td>
                            <td class="text-right">{{ number_format((int) $product->reorder_point) }}</td>
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
