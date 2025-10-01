@extends('layouts.admin')

@section('title', 'ผลการนำเข้า')
@section('page_title', 'สรุปผลการนำเข้าสินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.import.index') }}">นำเข้าไฟล์</a></li>
    <li class="breadcrumb-item active">สรุปผล</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-plus"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">สร้างสินค้าใหม่</span>
                <span class="info-box-number">{{ number_format($summary['created'] ?? 0) }} รายการ</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-sync-alt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">อัปเดตสินค้า</span>
                <span class="info-box-number">{{ number_format($summary['updated'] ?? 0) }} รายการ</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-forward"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">ข้ามรายการ</span>
                <span class="info-box-number">{{ number_format($summary['skipped'] ?? 0) }} แถว</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">แถวผิดพลาด</span>
                <span class="info-box-number">{{ number_format($summary['errors'] ?? 0) }} แถว</span>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="mb-3">การนำเข้าสินค้าเสร็จสิ้น หากพบแถวที่ผิดพลาดสามารถดาวน์โหลดไฟล์รายงานข้อผิดพลาดเพื่อแก้ไขและนำเข้าใหม่ได้</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.import.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left mr-1"></i> นำเข้าไฟล์ใหม่
            </a>
            @if(!empty($errorUrl))
                <a href="{{ $errorUrl }}" class="btn btn-danger">
                    <i class="fas fa-file-download mr-1"></i> ดาวน์โหลด error.csv
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
