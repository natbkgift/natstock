@extends('layouts.admin')

@section('title', 'สินค้า')
@section('page_title', 'จัดการสินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">สินค้า</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="mb-0">หน้าจัดการสินค้าจะมาภายหลังใน Phase ถัดไป ขณะนี้แสดงเป็น placeholder ภาษาไทย.</p>
    </div>
</div>
@endsection
