@extends('layouts.admin')

@section('title', 'นำเข้าไฟล์')
@section('page_title', 'นำเข้าข้อมูลสินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">นำเข้าไฟล์</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="mb-0">หน้าสำหรับอัปโหลดไฟล์ Excel/CSV จะถูกพัฒนาใน Phase ถัดไป.</p>
    </div>
</div>
@endsection
