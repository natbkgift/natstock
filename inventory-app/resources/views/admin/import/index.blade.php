@extends('layouts.admin')

@section('title', 'นำเข้าไฟล์สินค้า')
@section('page_title', 'นำเข้าสินค้าจากไฟล์')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">นำเข้าไฟล์</li>
@endsection

@section('content')
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h3 class="card-title mb-0">อัปโหลดไฟล์ CSV / XLSX</h3>
    </div>
    <div class="card-body">
        <p class="text-muted">รองรับไฟล์เข้ารหัส UTF-8 เท่านั้น กรุณาดาวน์โหลดเทมเพลตก่อนกรอกข้อมูลเพื่อป้องกันหัวคอลัมน์ไม่ตรงรูปแบบ</p>
        @php($pricingEnabled = config('inventory.enable_price'))
        <div class="mb-4 d-flex gap-2 flex-wrap">
            <a href="{{ asset($pricingEnabled ? 'templates/product_template.csv' : 'templates/product_template_no_price.csv') }}" class="btn btn-outline-primary btn-sm" download>
                <i class="fas fa-file-csv mr-1"></i> ดาวน์โหลดเทมเพลต (CSV)
            </a>
            <a href="{{ asset($pricingEnabled ? 'templates/product_template.xlsx' : 'templates/product_template_no_price.xlsx') }}" class="btn btn-outline-success btn-sm" download>
                <i class="fas fa-file-excel mr-1"></i> ดาวน์โหลดเทมเพลต (XLSX)
            </a>
        </div>
        @unless($pricingEnabled)
            <p class="text-muted">ระบบนี้ปิดการใช้งานราคาทุน/ราคาขายแล้ว เทมเพลตจะไม่มีคอลัมน์ราคาด้วย</p>
        @endunless
        <form action="{{ route('admin.import.preview') }}" method="POST" enctype="multipart/form-data" id="import-form">
            @csrf
            <div class="form-group">
                <label for="file">เลือกไฟล์นำเข้า <span class="text-danger">*</span></label>
                <input type="file" name="file" id="file" class="form-control" accept=".csv,.xlsx" required>
                <small class="form-text text-muted">ขนาดไฟล์ไม่เกิน 10 MB</small>
            </div>
            <div class="form-group">
                <label class="d-block">โหมดเมื่อพบ SKU ซ้ำ</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="mode-upsert" name="duplicate_mode" value="UPSERT" class="custom-control-input" checked>
                    <label class="custom-control-label" for="mode-upsert">UPSERT - ปรับปรุงข้อมูลสินค้าและปรับยอดคงเหลือ</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="mode-skip" name="duplicate_mode" value="SKIP" class="custom-control-input">
                    <label class="custom-control-label" for="mode-skip">SKIP - ข้ามแถวที่มี SKU ซ้ำ</label>
                </div>
            </div>
            <div class="form-group">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" class="custom-control-input" id="auto-create-category" name="auto_create_category" value="1">
                    <label class="custom-control-label" for="auto-create-category">สร้างหมวดหมู่อัตโนมัติหากไม่พบชื่อหมวดหมู่</label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search mr-1"></i> พรีวิวไฟล์
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('import-form').addEventListener('submit', function () {
        const button = this.querySelector('button[type="submit"]');
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status"></span> กำลังประมวลผล...';
    });
</script>
@endpush
