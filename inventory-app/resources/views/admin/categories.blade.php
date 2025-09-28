@extends('layouts.admin')

@section('title', 'หมวดหมู่')
@section('page_title', 'หมวดหมู่สินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">หมวดหมู่</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="mb-0">พื้นที่สำหรับบริหารหมวดหมู่สินค้า จะเติมเนื้อหาใน Phase 2–5.</p>
    </div>
</div>
@endsection
