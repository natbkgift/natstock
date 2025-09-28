@extends('layouts.admin')

@section('title', 'เพิ่มสินค้า')
@section('page_title', 'เพิ่มสินค้าใหม่')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">สินค้า</a></li>
    <li class="breadcrumb-item active">เพิ่มสินค้า</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
@endpush

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">ฟอร์มเพิ่มสินค้า</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('admin.products.store') }}" method="POST">
            @csrf
            @include('admin.products.partials.form', ['product' => null])
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            language: {
                noResults: () => 'ไม่พบข้อมูล',
                searching: () => 'กำลังค้นหา...'
            }
        });
    });
</script>
@endpush
