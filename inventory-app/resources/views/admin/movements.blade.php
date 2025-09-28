@extends('layouts.admin')

@section('title', 'เคลื่อนไหวสต็อก')
@section('page_title', 'บันทึกเคลื่อนไหวสต็อก')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">เคลื่อนไหวสต็อก</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <p class="mb-0">จะใช้สำหรับบันทึกการรับเข้าและตัดออกของสินค้าในอนาคต ขณะนี้เป็นหน้าเปล่า.</p>
    </div>
</div>
@endsection
