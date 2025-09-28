@extends('layouts.admin')

@section('title', 'บันทึกกิจกรรมระบบ')
@section('page_title', 'บันทึกกิจกรรมระบบ')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">บันทึกกิจกรรมระบบ</li>
@endsection

@section('content')
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">ค้นหากิจกรรม</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.audit.index') }}" class="form-inline">
                <div class="form-group mr-2">
                    <label for="action" class="mr-2">ประเภทกิจกรรม</label>
                    <select name="action" id="action" class="form-control">
                        <option value="">-- ทั้งหมด --</option>
                        @foreach($actions as $availableAction)
                            <option value="{{ $availableAction }}" {{ $filters['action'] === $availableAction ? 'selected' : '' }}>{{ $availableAction }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group mr-2">
                    <label for="keyword" class="mr-2">คำค้น</label>
                    <input type="text" name="keyword" id="keyword" class="form-control" value="{{ $filters['keyword'] }}" placeholder="คำอธิบายหรือรายละเอียด">
                </div>
                <div class="form-group mr-2">
                    <label for="date_from" class="mr-2">จากวันที่</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="form-group mr-2">
                    <label for="date_to" class="mr-2">ถึงวันที่</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                <a href="{{ route('admin.audit.index') }}" class="btn btn-outline-secondary ml-2">ล้างตัวกรอง</a>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">รายการกิจกรรมล่าสุด</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead>
                    <tr>
                        <th>เวลา</th>
                        <th>ผู้ทำรายการ</th>
                        <th>ประเภท</th>
                        <th>คำอธิบาย</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ $activity->happened_at?->timezone('Asia/Bangkok')->format('d/m/Y H:i:s') }}</td>
                            <td>{{ $activity->actor?->name ?? 'ระบบ' }}</td>
                            <td><span class="badge badge-info">{{ $activity->action }}</span></td>
                            <td>{{ $activity->description ?? '-' }}</td>
                            <td style="max-width: 360px;">
                                @if(!empty($activity->properties))
                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 12px;">{{ json_encode($activity->properties, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">ยังไม่มีข้อมูลกิจกรรม</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $activities->links() }}
        </div>
    </div>
@endsection
