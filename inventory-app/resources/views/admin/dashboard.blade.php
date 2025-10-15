@extends('layouts.admin')

@section('title', 'แดชบอร์ด')
@section('page_title', 'แดชบอร์ดภาพรวม')
@section('breadcrumbs')
    <li class="breadcrumb-item active">แดชบอร์ด</li>
@endsection

@php($activityPresenter = app(\App\Support\ActivityPresenter::class))

@section('content')
@if($shouldShowAlerts)
    @include('dashboard._alerts')
@endif
<div class="row">
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-outline card-warning h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">ล็อตใกล้หมดอายุ ({{ number_format($expiringCount) }})</h3>
                <span class="badge badge-warning">ภายใน {{ $expiringDays }} วัน</span>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <span class="display-4 text-warning">{{ number_format($expiringCount) }}</span>
                    <a href="{{ route('admin.reports.expiring-batches') }}" class="btn btn-link p-0">ไปยังรายงาน</a>
                </div>
                <p class="text-muted mb-0">สรุปล็อตสินค้าที่หมดอายุภายใน {{ $expiringDays }} วัน</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-outline card-danger h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">สินค้าสต็อกต่ำ ({{ number_format($lowStockCount) }})</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <span class="display-4 text-danger">{{ number_format($lowStockCount) }}</span>
                    <a href="{{ route('admin.reports.low-stock') }}" class="btn btn-link text-danger p-0">ไปยังรายงาน</a>
                </div>
                <p class="text-muted mb-0">ตรวจสอบสินค้าที่ปริมาณคงเหลือน้อยกว่าจุดสั่งซื้อซ้ำ</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">สรุปรายการสินค้า</h3>
                <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-secondary">ดูทั้งหมด</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>ชื่อสินค้า</th>
                                <th class="text-right">คงเหลือรวม</th>
                                <th class="text-right">จำนวนล็อต</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($productSummary as $item)
                                <tr>
                                    <td>{{ $item['sku'] }}</td>
                                    <td>{{ $item['name'] }}</td>
                                    <td class="text-right">{{ number_format($item['qty']) }}</td>
                                    <td class="text-right">{{ number_format($item['active_batches']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">ยังไม่มีข้อมูลสินค้า</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">รายการอัปเดตล่าสุด</h3>
        <a href="{{ route('admin.movements.index') }}" class="btn btn-sm btn-outline-secondary">ไปหน้ารายการทั้งหมด</a>
    </div>
    <div class="card-body p-0">
        <ul class="list-group list-group-flush">
            @forelse($recentMovements as $movement)
                <li class="list-group-item">
                    <div>{{ $activityPresenter->presentMovement($movement) }}</div>
                    @if($movement->batch)
                        @if($movement->batch->expire_date)
                            <small class="text-muted d-block">หมดอายุ {{ $movement->batch->expire_date->locale('th')->translatedFormat('d M Y') }}</small>
                        @else
                            <small class="text-muted d-block">ไม่ระบุวันหมดอายุ</small>
                        @endif
                    @else
                        <small class="text-muted d-block">ไม่มีข้อมูลล็อต (legacy)</small>
                    @endif
                    @if($movement->note)
                        <small class="text-muted d-block">หมายเหตุ: {{ $movement->note }}</small>
                    @endif
                </li>
            @empty
                <li class="list-group-item text-center text-muted py-4">ยังไม่มีบันทึกการเคลื่อนไหว</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
