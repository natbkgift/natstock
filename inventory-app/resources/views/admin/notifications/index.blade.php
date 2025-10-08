@extends('layouts.admin')

@section('title', 'การแจ้งเตือน')
@section('page_title', 'การแจ้งเตือน')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">การแจ้งเตือน</li>
@endsection

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">สรุปการแจ้งเตือนล่าสุด</h3>
            <form method="POST" action="{{ route('admin.notifications.mark-all') }}" onsubmit="return confirm('ยืนยันทำเครื่องหมายการแจ้งเตือนทั้งหมดว่าอ่านแล้วหรือไม่?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-secondary">ทำเครื่องหมายทั้งหมดว่าอ่านแล้ว</button>
            </form>
        </div>
        <div class="card-body">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $summary = $data['payload']['summary'] ?? [
                        'expiring' => ['enabled' => false, 'count' => 0, 'items' => [], 'days' => 0],
                        'low_stock' => ['enabled' => false, 'count' => 0, 'items' => []],
                    ];
                    $links = $data['payload']['links'] ?? [];
                    $isUnread = is_null($notification->read_at);
                @endphp
                <div class="card card-outline {{ $isUnread ? 'card-warning' : 'card-secondary' }} mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $data['title'] ?? 'แจ้งเตือนระบบ' }}</strong>
                            @if(!empty($data['payload']['is_test']))
                                <span class="badge badge-info ml-2">ทดสอบ</span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $notification->created_at->timezone('Asia/Bangkok')->format('d/m/Y H:i') }}</small>
                    </div>
                    <div class="card-body">
                        <h5 class="font-weight-bold">ล็อตใกล้หมดอายุ</h5>
                        @if($summary['expiring']['enabled'])
                            <p>ภายใน {{ $summary['expiring']['days'] }} วัน: {{ $summary['expiring']['count'] }} ล็อต</p>
                            @if(!empty($summary['expiring']['items']))
                                <ul class="mb-3">
                                    @foreach($summary['expiring']['items'] as $item)
                                        <li>{{ $item['sku'] }} - {{ $item['name'] }} (ล็อต {{ $item['sub_sku'] ?? '-' }}) หมดอายุ {{ $item['expire_date_thai'] ?? '-' }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">ยังไม่มีล็อตที่ใกล้หมดอายุในช่วงที่ตั้งไว้</p>
                            @endif
                        @else
                            <p class="text-muted">ไม่ได้เปิดใช้งานการแจ้งเตือนล็อตใกล้หมดอายุ</p>
                        @endif
                        <h5 class="font-weight-bold">สินค้าสต็อกต่ำ</h5>
                        @if($summary['low_stock']['enabled'])
                            <p>จำนวนทั้งหมด: {{ $summary['low_stock']['count'] }} รายการ</p>
                            @if(!empty($summary['low_stock']['items']))
                                <ul>
                                    @foreach($summary['low_stock']['items'] as $item)
                                        <li>{{ $item['sku'] }} - {{ $item['name'] }} (คงเหลือ {{ $item['qty'] }} / จุดสั่งซื้อ {{ $item['reorder_point'] }})</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-muted">ยังไม่มีรายการที่สต็อกต่ำในตอนนี้</p>
                            @endif
                        @else
                            <p class="text-muted">ไม่ได้เปิดการแจ้งเตือนสต็อกต่ำ</p>
                        @endif
                        <div class="mt-3">
                            <a href="{{ $links['expiring'] ?? route('admin.reports.expiring-batches') }}" class="btn btn-sm btn-outline-primary mr-2" target="_blank">ดูรายงานล็อตใกล้หมดอายุ</a>
                            <a href="{{ $links['low_stock'] ?? route('admin.reports.low-stock') }}" class="btn btn-sm btn-outline-primary" target="_blank">ดูรายงานสินค้าสต็อกต่ำ</a>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        @if($isUnread)
                            <form method="POST" action="{{ route('admin.notifications.mark-as-read', $notification->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">ทำเครื่องหมายว่าอ่านแล้ว</button>
                            </form>
                        @else
                            <span class="text-muted">อ่านแล้ว</span>
                        @endif
                        <span class="text-muted">บันทึกเมื่อ {{ $notification->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @empty
                <p class="text-center text-muted mb-0">ยังไม่มีการแจ้งเตือนในระบบ</p>
            @endforelse
        </div>
        <div class="card-footer">
            {{ $notifications->links() }}
        </div>
    </div>
@endsection
