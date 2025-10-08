@extends('layouts.admin')

@section('title', 'ตั้งค่าระบบ')
@section('page_title', 'ตั้งค่าระบบ')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">ตั้งค่าระบบ</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">การตั้งค่าการแจ้งเตือน</h3>
                </div>
                <form method="POST" action="{{ route('admin.settings.update') }}">
                    @csrf
                    <div class="card-body">
                        <div class="form-group">
                            <label for="alert_expiring_days">ช่วงวันแจ้งเตือนสินค้าใกล้หมดอายุ (คั่นด้วย ,)</label>
                            <input type="text" name="alert_expiring_days" id="alert_expiring_days" class="form-control" value="{{ old('alert_expiring_days', $values['alert_expiring_days']) }}" required>
                            <small class="form-text text-muted">ตัวอย่าง: 30,60,90</small>
                        </div>
                        <div class="form-group">
                            <label for="expiring_days">จำนวนวันล่วงหน้าสำหรับแจ้งเตือนล็อตใกล้หมดอายุ</label>
                            <input type="number" name="expiring_days" id="expiring_days" min="1" max="365" class="form-control" value="{{ old('expiring_days', $values['expiring_days']) }}" required>
                            <small class="form-text text-muted">ค่าเริ่มต้น 30 วัน</small>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="expiring_enabled" name="expiring_enabled" value="1" {{ old('expiring_enabled', $values['expiring_enabled']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="expiring_enabled">เปิดการแจ้งเตือนล็อตใกล้หมดอายุในระบบ</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="notify_low_stock" name="notify_low_stock" value="1" {{ old('notify_low_stock', $values['notify_low_stock']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="notify_low_stock">เปิดการแจ้งเตือนสต็อกต่ำ</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="low_stock_enabled" name="low_stock_enabled" value="1" {{ old('low_stock_enabled', $values['low_stock_enabled']) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="low_stock_enabled">แสดงป๊อปอัปแจ้งเตือนสต็อกต่ำบนแดชบอร์ด</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>ช่องทางการแจ้งเตือน</label>
                            <div class="row">
                                @foreach($channelOptions as $channelKey => $channelLabel)
                                    <div class="col-sm-4">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="channel_{{ $channelKey }}" name="notify_channels[]" value="{{ $channelKey }}" {{ in_array($channelKey, old('notify_channels', $values['notify_channels']), true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="channel_{{ $channelKey }}">{{ $channelLabel }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="notify_emails">อีเมลสำหรับแจ้งเตือน (คั่นด้วย ,)</label>
                            <textarea name="notify_emails" id="notify_emails" class="form-control" rows="2" placeholder="เช่น manager@example.com,owner@example.com">{{ old('notify_emails', $values['notify_emails']) }}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="daily_scan_time">เวลาในการสแกนแจ้งเตือนอัตโนมัติ</label>
                            <input type="time" name="daily_scan_time" id="daily_scan_time" class="form-control" value="{{ old('daily_scan_time', $values['daily_scan_time']) }}" required>
                            <small class="form-text text-muted">ระบุเวลาเป็นเวลาไทย (Asia/Bangkok)</small>
                        </div>
                        <div class="form-group">
                            <label>สถานะการตั้งค่า LINE Notify</label>
                            @if($lineTokenConfigured)
                                <span class="badge badge-success">ตั้งค่าแล้ว</span>
                            @else
                                <span class="badge badge-secondary">ยังไม่ตั้งค่า</span>
                            @endif
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
                        <span class="text-muted">อัปเดตล่าสุดจะมีผลกับการสแกนอัตโนมัติครั้งถัดไป</span>
                    </div>
                </form>
            </div>
            <div class="card card-outline card-info mt-3">
                <div class="card-header">
                    <h3 class="card-title">ทดสอบการแจ้งเตือน</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">ระบบจะส่งข้อความตัวอย่างไปยังช่องทางที่เปิดใช้งานอยู่</p>
                    <form method="POST" action="{{ route('admin.settings.test-notification') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-info">ส่งการแจ้งเตือนทดสอบ</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">เครื่องมือ</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">ใช้สำหรับรันคำสั่งสแกนแจ้งเตือนทันที</p>
                    <form method="POST" action="{{ route('admin.settings.run-scan') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-block" onclick="return confirm('ยืนยันการสแกนแจ้งเตือนตอนนี้หรือไม่?')">รันสแกนตอนนี้</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
