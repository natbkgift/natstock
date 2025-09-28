@extends('layouts.admin')

@section('title', 'เพิ่มหมวดหมู่')
@section('page_title', 'เพิ่มหมวดหมู่สินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">หมวดหมู่สินค้า</a></li>
    <li class="breadcrumb-item active">เพิ่มหมวดหมู่</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">ฟอร์มเพิ่มหมวดหมู่</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.categories.store') }}" method="POST">
            @csrf
            @include('admin.categories.partials.form', ['category' => null])
        </form>
    </div>
</div>
@endsection
