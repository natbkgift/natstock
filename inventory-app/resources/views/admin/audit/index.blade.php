@extends('layouts.admin')

@section('title', 'บันทึกกิจกรรม')
@section('page_title', 'บันทึกกิจกรรม')

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">บันทึกกิจกรรม</li>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">รายการกิจกรรมล่าสุด</h3>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>เวลา</th>
                        <th>ผู้ใช้งาน</th>
                        <th>กิจกรรม</th>
                        <th>รายละเอียด</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        <tr>
                            <td>{{ ($activity->happened_at ?? $activity->created_at)?->format('d/m/Y H:i') }}</td>
                            <td>{{ $activity->actor->name ?? 'N/A' }}</td>
                            <td>{{ $activity->description }}</td>
                            <td>
                                @php $props = $activity->properties ?? []; @endphp
                                @if(is_array($props) && count($props))
                                    <ul>
                                        @foreach($props as $key => $value)
                                            <li><strong>{{ $key }}:</strong> {{ is_array($value) ? json_encode($value) : (string) $value }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">ยังไม่มีกิจกรรม</td>
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