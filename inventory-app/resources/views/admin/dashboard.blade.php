@extends('layouts.admin')

@section('title', 'แดชบอร์ด')
@section('page_title', 'แดชบอร์ดภาพรวม')
@section('breadcrumbs')
    <li class="breadcrumb-item active">แดชบอร์ด</li>
@endsection

@php
    $typeLabels = ['in' => 'รับเข้า', 'out' => 'เบิกออก', 'adjust' => 'ปรับยอด'];
    $typeClasses = ['in' => 'success', 'out' => 'danger', 'adjust' => 'warning'];
@endphp

@section('content')
<div class="row">
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-outline card-warning h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">ใกล้หมดอายุ</h3>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-warning dropdown-toggle" type="button" data-toggle="dropdown">
                        ภายใน {{ $expiringDays }} วัน
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        @foreach([30, 60, 90] as $days)
                            <a class="dropdown-item {{ $expiringDays === $days ? 'active' : '' }}" href="{{ route('admin.dashboard', ['expiring_days' => $days]) }}">
                                ภายใน {{ $days }} วัน ({{ number_format($expiringCounts[$days]) }})
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="display-4 text-warning">{{ number_format($selectedExpiringCount) }}</span>
                    <a href="{{ route('admin.products.index', ['expiring' => $expiringDays]) }}" class="btn btn-link p-0">ดูสินค้า</a>
                </div>
                <p class="text-muted mb-0">สินค้าใกล้หมดอายุภายใน {{ $expiringDays }} วัน</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card card-outline card-danger h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">สต็อกต่ำกว่าจุดสั่งซื้อซ้ำ</h3>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-2">
                    <span class="display-4 text-danger">{{ number_format($lowStockCount) }}</span>
                    <a href="{{ route('admin.products.index', ['low_stock' => 1]) }}" class="btn btn-link text-danger p-0">ดูสินค้า</a>
                </div>
                <p class="text-muted mb-0">รายการที่ควรเติมสต็อกโดยด่วน</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 col-md-12 mb-4">
        <div class="card card-outline card-success h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">มูลค่าสต็อกตามราคาทุนรวม</h3>
            </div>
            <div class="card-body">
                @if($pricingEnabled)
                    <div class="display-4 text-success mb-2">{{ $stockValueFormatted }}</div>
                    <p class="text-muted mb-0">คิดจากปริมาณคงเหลือ x ราคาทุน</p>
                @else
                    <div class="display-4 text-muted mb-2"><i class="fas fa-lock"></i></div>
                    <p class="text-muted mb-0">ระบบนี้ปิดการใช้งานราคาทุน/ราคาขายแล้ว</p>
                @endif
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
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width: 20%">วันที่/เวลา</th>
                        <th style="width: 35%">สินค้า</th>
                        <th style="width: 15%">ประเภท</th>
                        <th style="width: 15%" class="text-right">จำนวน</th>
                        <th style="width: 15%">ผู้ปฏิบัติ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentMovements as $movement)
                        <tr>
                            <td>{{ $movement->happened_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>[{{ $movement->product->sku ?? '-' }}]</strong>
                                <div>{{ $movement->product->name ?? '-' }}</div>
                            </td>
                            <td>
                                <span class="badge badge-{{ $typeClasses[$movement->type] ?? 'secondary' }}">{{ $typeLabels[$movement->type] ?? '-' }}</span>
                            </td>
                            <td class="text-right">{{ $movement->formatted_qty }}</td>
                            <td>{{ $movement->actor->name ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ยังไม่มีบันทึกการเคลื่อนไหว</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
