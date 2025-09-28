@extends('layouts.admin')

@section('title', 'รายงาน')
@section('page_title', 'สรุปรายงานสต็อก')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">รายงาน</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="mb-0">รายงานสินค้าคงคลังและรายงานเฉพาะจะถูกสร้างใน Phase ต่อไป.</p>
    </div>
</div>
@endsection
