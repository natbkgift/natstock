@extends('layouts.admin')

@section('title', 'รายงาน')
@section('page_title', 'ศูนย์รวมรายงาน')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">รายงาน</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="card card-outline card-warning h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">รายงานใกล้หมดอายุ</h3>
                <i class="fas fa-hourglass-half text-warning"></i>
            </div>
            <div class="card-body">
                <p class="mb-2">ติดตามสินค้าที่กำลังจะหมดอายุภายใน 30, 60 หรือ 90 วัน พร้อมตัวกรองตามหมวดหมู่และสถานะ.</p>
                <a href="{{ route('admin.reports.expiring') }}" class="btn btn-warning btn-block text-white">เปิดรายงาน</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-outline card-danger h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">รายงานสต็อกต่ำ</h3>
                <i class="fas fa-boxes text-danger"></i>
            </div>
            <div class="card-body">
                <p class="mb-2">ระบุสินค้าใดบ้างที่คงเหลือต่ำกว่าจุดสั่งซื้อซ้ำ เพื่อให้ทีมจัดซื้อวางแผนทันเวลา.</p>
                <a href="{{ route('admin.reports.low-stock') }}" class="btn btn-danger btn-block">เปิดรายงาน</a>
            </div>
        </div>
    </div>
    @if($pricingEnabled ?? config('inventory.enable_price'))
        <div class="col-md-4">
            <div class="card card-outline card-success h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">รายงานมูลค่าสต็อก</h3>
                    <i class="fas fa-coins text-success"></i>
                </div>
                <div class="card-body">
                    <p class="mb-2">สรุปมูลค่าสินค้าคงคลังแบบเรียลไทม์ พร้อมยอดรวมเพื่อใช้ในการวางแผนการเงิน.</p>
                    <a href="{{ route('admin.reports.valuation') }}" class="btn btn-success btn-block">เปิดรายงาน</a>
                </div>
            </div>
        </div>
    @else
        <div class="col-md-4">
            <div class="card card-outline card-secondary h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">รายงานมูลค่าสต็อก</h3>
                    <i class="fas fa-lock text-secondary"></i>
                </div>
                <div class="card-body">
                    <p class="mb-2 text-muted">ระบบนี้ปิดการใช้งานราคาทุน/ราคาขาย รายงานมูลค่าสต็อกจึงถูกซ่อนไว้ชั่วคราว</p>
                    <button class="btn btn-secondary btn-block" disabled>ปิดใช้งาน</button>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
