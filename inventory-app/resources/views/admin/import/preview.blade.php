@extends('layouts.admin')

@section('title', 'พรีวิวการนำเข้า')
@section('page_title', 'ตรวจสอบไฟล์นำเข้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.import.index') }}">นำเข้าไฟล์</a></li>
    <li class="breadcrumb-item active">พรีวิว</li>
@endsection

@section('content')
@if($fileError)
    <div class="alert alert-danger">
        <strong>เกิดข้อผิดพลาด:</strong> {{ $fileError }}<br>
        กรุณาตรวจสอบไฟล์แล้วอัปโหลดใหม่อีกครั้ง
    </div>
@endif

<div class="alert alert-info">
    <strong>ชื่อไฟล์:</strong> {{ $summary['original_name'] ?? 'ไม่ทราบชื่อไฟล์' }}<br>
    <strong>จำนวนแถวทั้งหมด:</strong> {{ number_format($summary['total_rows'] ?? 0) }} แถว<br>
    <strong>โหมดซ้ำ:</strong> {{ $summary['duplicate_mode'] === 'UPSERT' ? 'UPSERT - ปรับปรุงข้อมูล' : 'SKIP - ข้ามแถวซ้ำ' }}<br>
    <strong>สร้างหมวดหมู่อัตโนมัติ:</strong> {{ $summary['auto_create_category'] ? 'เปิดใช้งาน' : 'ปิด' }}
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">ผลการตรวจสอบเบื้องต้น</h5>
                <p class="mb-0 text-muted">แสดงตัวอย่าง 20 แถวแรกของไฟล์ พร้อมสถานะการตรวจสอบ</p>
            </div>
            <div class="text-right">
                <span class="badge badge-success">ผ่าน: {{ number_format($summary['valid_rows'] ?? 0) }}</span>
                <span class="badge badge-warning">มีปัญหา: {{ number_format($summary['error_rows'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead class="thead-light">
                <tr>
                    <th style="width: 80px;">แถวที่</th>
                    <th style="width: 120px;">สถานะ</th>
                    <th>ข้อความตรวจสอบ</th>
                    <th>ค่าที่ตีความได้</th>
                </tr>
            </thead>
            <tbody>
                @forelse($previewRows as $row)
                    <tr>
                        <td>{{ $row['row_number'] }}</td>
                        <td>
                            @if($row['errors'] === [])
                                <span class="badge badge-success">ผ่าน</span>
                            @else
                                <span class="badge badge-danger">มีปัญหา</span>
                            @endif
                        </td>
                        <td>
                            @if($row['errors'] === [])
                                <span class="text-muted">-</span>
                            @else
                                <ul class="pl-3 mb-0">
                                    @foreach($row['errors'] as $message)
                                        <li>{{ $message }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td>
                            <dl class="row mb-0">
                                @foreach($row['normalized'] as $key => $value)
                                    <dt class="col-sm-4 text-sm-right">{{ $key }}</dt>
                                    <dd class="col-sm-8">{{ $value ?? '—' }}</dd>
                                @endforeach
                            </dl>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">ไม่พบข้อมูลตัวอย่าง</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<form action="{{ route('admin.import.commit') }}" method="POST" id="commit-form">
    @csrf
    <input type="hidden" name="file_token" value="{{ $fileToken }}">
    <input type="hidden" name="duplicate_mode" value="{{ $summary['duplicate_mode'] }}">
    <input type="hidden" name="auto_create_category" value="{{ $summary['auto_create_category'] ? '1' : '0' }}">
    <div class="d-flex justify-content-between">
        <a href="{{ route('admin.import.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-chevron-left mr-1"></i> กลับไปเลือกไฟล์ใหม่
        </a>
        <div>
            <button type="submit" class="btn btn-primary" {{ $canCommit ? '' : 'disabled' }}>
                <i class="fas fa-check mr-1"></i> คอมมิตข้อมูลเข้าระบบ
            </button>
            @if(!$canCommit)
                <p class="text-danger mt-2 mb-0">
                    ไม่สามารถคอมมิตได้เนื่องจากพบข้อผิดพลาดในไฟล์ กรุณาแก้ไขแล้วอัปโหลดใหม่
                </p>
            @endif
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    document.getElementById('commit-form').addEventListener('submit', function (event) {
        const button = this.querySelector('button[type="submit"]');
        if (button.hasAttribute('disabled')) {
            event.preventDefault();
            return;
        }
        button.disabled = true;
        button.innerHTML = '<span class="spinner-border spinner-border-sm mr-2" role="status"></span> กำลังคอมมิต...';
    });
</script>
@endpush
