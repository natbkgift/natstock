@php
    $lowStock = $alertStates['low_stock'];
    $expiring = $alertStates['expiring'];
@endphp

<div class="modal fade" id="dashboardAlertModal" tabindex="-1" role="dialog" aria-labelledby="dashboardAlertTitle" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="dashboardAlertTitle">แจ้งเตือนสถานะคลังสินค้า</h5>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="low-stock-tab" data-toggle="tab" href="#low-stock-pane" role="tab" aria-controls="low-stock-pane" aria-selected="true">
                            สต็อกต่ำ ({{ number_format($lowStock['count']) }})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="expiring-tab" data-toggle="tab" href="#expiring-pane" role="tab" aria-controls="expiring-pane" aria-selected="false">
                            ใกล้หมดอายุ ({{ number_format($expiring['count']) }})
                        </a>
                    </li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane fade show active" id="low-stock-pane" role="tabpanel" aria-labelledby="low-stock-tab" data-alert-type="low_stock" data-payload-hash="{{ $lowStock['payload_hash'] }}">
                        @if(!empty($lowStock['items']))
                            <ul class="list-group list-group-flush">
                                @foreach($lowStock['items'] as $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $item['sku'] }}</strong>
                                            <div class="small text-muted">{{ $item['name'] }}</div>
                                        </div>
                                        <span class="badge badge-danger badge-pill">{{ $item['qty_total'] ?? $item['qty'] ?? 0 }} / {{ $item['reorder_point'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center text-muted py-4">ไม่มีสินค้าที่สต็อกต่ำในขณะนี้</div>
                        @endif
                        <div class="mt-3 text-right">
                            <a href="{{ route('admin.reports.low-stock') }}" class="btn btn-link">ดูทั้งหมด</a>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="expiring-pane" role="tabpanel" aria-labelledby="expiring-tab" data-alert-type="expiring" data-payload-hash="{{ $expiring['payload_hash'] }}" data-alert-days="{{ $expiring['days'] ?? 0 }}">
                        @if(!empty($expiring['items']))
                            <ul class="list-group list-group-flush">
                                @foreach($expiring['items'] as $item)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>{{ $item['sku'] }}</strong>
                                            <div class="small text-muted">{{ $item['name'] }} @if(!empty($item['sub_sku']))<span class="text-secondary">(ล็อต {{ $item['sub_sku'] }})</span>@endif</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="font-weight-bold">{{ $item['expire_date'] ? \Carbon\Carbon::parse($item['expire_date'])->format('d/m/Y') : '-' }}</div>
                                            <span class="badge badge-warning badge-pill">{{ $item['qty'] }} ชิ้น</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="text-center text-muted py-4">ยังไม่มีล็อตที่ใกล้หมดอายุ</div>
                        @endif
                        <div class="mt-3 text-right">
                            <a href="{{ route('admin.reports.expiring-batches') }}" class="btn btn-link">ดูทั้งหมด</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div class="text-muted">แจ้งเตือนนี้จะอยู่จนกว่าจะทำเครื่องหมายว่าอ่านแล้วหรือกดงดเตือน</div>
                <div>
                    <button type="button" class="btn btn-outline-secondary mr-2" data-alert-action="snooze">งดเตือนชั่วคราว (ถึงพรุ่งนี้)</button>
                    <button type="button" class="btn btn-primary" data-alert-action="mark-read">ทำเครื่องหมายว่าอ่านแล้ว</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        $('#dashboardAlertModal').modal('show');

        const modal = document.getElementById('dashboardAlertModal');
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        modal.querySelectorAll('[data-alert-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                const action = this.getAttribute('data-alert-action');
                const activePane = modal.querySelector('.tab-pane.active');
                const alertType = activePane.getAttribute('data-alert-type');
                const payloadHash = activePane.getAttribute('data-payload-hash');

                if (!payloadHash) {
                    return;
                }

                fetch(action === 'mark-read' ? '{{ route('admin.alerts.mark-read') }}' : '{{ route('admin.alerts.snooze') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        type: alertType,
                        payload_hash: payloadHash,
                    }),
                }).then(function (response) {
                    if (response.ok) {
                        $('#dashboardAlertModal').modal('hide');
                        window.location.reload();
                    }
                });
            });
        });
    });
</script>
@endpush
