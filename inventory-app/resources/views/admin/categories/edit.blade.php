@extends('layouts.admin')

@section('title', 'แก้ไขหมวดหมู่')
@section('page_title', 'แก้ไขหมวดหมู่สินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.categories.index') }}">หมวดหมู่สินค้า</a></li>
    <li class="breadcrumb-item active">แก้ไขหมวดหมู่</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">ฟอร์มแก้ไขหมวดหมู่</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.categories.partials.form', ['category' => $category])
        </form>
    </div>
</div>
@endsection
