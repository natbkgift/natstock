@extends('layouts.admin')

@section('title', 'แดชบอร์ด')
@section('page_title', 'แดชบอร์ดภาพรวม')
@section('breadcrumbs')
    <li class="breadcrumb-item active">แดชบอร์ด</li>
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <h2 class="h5">สรุปสถานะคลังสินค้า</h2>
        <p class="mb-0">หน้านี้จะแสดงภาพรวมสินค้าคงคลัง สถานะสินค้าใกล้หมด และคำแนะนำการเติมสต็อกใน Phase ถัดไป.</p>
    </div>
</div>
@endsection
