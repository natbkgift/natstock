@extends('layouts.admin')

@section('title', 'สำรองข้อมูลระบบ')
@section('page_title', 'สำรองข้อมูลระบบ')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">สำรองข้อมูลระบบ</li>
@endsection

@section('content')
    <div class="alert alert-warning">
        <strong>คำเตือน:</strong> ไฟล์สำรองมีข้อมูลสำคัญ กรุณาจัดเก็บในที่ปลอดภัยและจำกัดสิทธิ์การเข้าถึง
    </div>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">สร้างไฟล์สำรองใหม่</h3>
            <form method="POST" action="{{ route('admin.backup.store') }}" onsubmit="return confirm('ยืนยันการสร้างไฟล์สำรองตอนนี้หรือไม่?');">
                @csrf
                <button type="submit" class="btn btn-primary">สำรองตอนนี้</button>
            </form>
        </div>
        <div class="card-body">
            <p class="mb-0">ระบบจะสำรองฐานข้อมูลและไฟล์สำคัญ (storage/app/public, storage/app/tmp) เก็บไว้ที่ <code>storage/app/backups</code> โดยอัตโนมัติ</p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">รายการไฟล์สำรอง (เก็บล่าสุด {{ \App\Services\BackupService::RETENTION ?? 7 }} ชุด)</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>ชื่อไฟล์</th>
                    <th>ขนาด</th>
                    <th>สร้างเมื่อ</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($backups as $backup)
                    <tr>
                        <td>{{ $backup['name'] }}</td>
                        <td>{{ number_format($backup['size'] / 1024, 2) }} KB</td>
                        <td>{{ $backup['created_at']->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('admin.backup.download', $backup['name']) }}" class="btn btn-sm btn-outline-success">ดาวน์โหลด</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">ยังไม่มีไฟล์สำรอง</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
