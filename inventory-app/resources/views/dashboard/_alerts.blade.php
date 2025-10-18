@php
    use Carbon\Carbon;

    $lowStock = $alerts['low_stock'] ?? ['count' => 0, 'items' => []];
    $expiring = $alerts['expiring'] ?? ['count' => 0, 'items' => [], 'days' => 0];
    $today = Carbon::today();
    $hasAlerts = ($lowStock['count'] ?? 0) > 0 || ($expiring['count'] ?? 0) > 0;
@endphp

@if($hasAlerts)
    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card border-danger shadow-sm h-100">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0">สินค้าสต็อกต่ำ</h3>
                        <small class="d-block text-white-50">แสดงเฉพาะสินค้าที่ต่ำกว่าจุดสั่งซื้อซ้ำ</small>
                    </div>
                    <span class="badge badge-light text-danger">{{ number_format($lowStock['count'] ?? 0) }} รายการ</span>
                </div>
                <div class="card-body p-0">
                    @if(!empty($lowStock['items']))
                        <div class="list-group list-group-flush">
                            @foreach($lowStock['items'] as $item)
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                                    <div class="mb-2 mb-md-0">
                                        <div class="font-weight-bold">{{ $item['sku'] }}</div>
                                        <div class="text-muted">{{ $item['name'] }}</div>
                                    </div>
                                    <div class="text-md-right">
                                        <span class="badge badge-pill badge-danger">คงเหลือ {{ number_format($item['qty_total'] ?? 0) }}</span>
                                        <div class="small text-muted">จุดสั่งซื้อซ้ำ {{ number_format($item['reorder_point'] ?? 0) }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">ไม่มีสินค้าที่สต็อกต่ำในขณะนี้</div>
                    @endif
                </div>
                <div class="card-footer bg-light text-right">
                    <a href="{{ route('admin.reports.low-stock') }}" class="btn btn-sm btn-outline-danger">ดูรายงานสต็อกต่ำทั้งหมด</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card border-warning shadow-sm h-100">
                <div class="card-header bg-warning d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title mb-0 text-dark">ล็อตใกล้หมดอายุ / หมดอายุ</h3>
                        <small class="d-block text-dark">ภายใน {{ number_format($expiring['days'] ?? 0) }} วันข้างหน้า</small>
                    </div>
                    <span class="badge badge-dark">{{ number_format($expiring['count'] ?? 0) }} ล็อต</span>
                </div>
                <div class="card-body p-0">
                    @if(!empty($expiring['items']))
                        <div class="list-group list-group-flush">
                            @foreach($expiring['items'] as $item)
                                @php
                                    $expireDate = isset($item['expire_date']) && $item['expire_date'] !== null
                                        ? Carbon::parse($item['expire_date'])->startOfDay()
                                        : null;
                                    $isExpired = $expireDate !== null && $expireDate->lt($today);
                                    $badgeClass = $isExpired ? 'badge-danger' : 'badge-warning';
                                    $badgeLabel = $isExpired ? 'หมดอายุแล้ว' : 'ใกล้หมดอายุ';
                                @endphp
                                <div class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                                    <div class="mb-2 mb-md-0">
                                        <div class="font-weight-bold">{{ $item['sku'] }} <span class="text-secondary">(LOT {{ $item['lot_no'] ?? $item['sub_sku'] ?? '-' }})</span></div>
                                        <div class="text-muted">{{ $item['name'] }}</div>
                                    </div>
                                    <div class="text-md-right">
                                        <span class="badge {{ $badgeClass }} badge-pill">{{ $badgeLabel }}</span>
                                        <div class="small text-muted">
                                            @if($expireDate)
                                                หมดอายุ {{ $expireDate->locale('th')->translatedFormat('d M Y') }}
                                            @else
                                                ไม่ระบุวันหมดอายุ
                                            @endif
                                        </div>
                                        <div class="small text-muted">คงเหลือ {{ number_format($item['qty'] ?? 0) }} ชิ้น</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">ยังไม่มีล็อตที่ใกล้หมดอายุ</div>
                    @endif
                </div>
                <div class="card-footer bg-light text-right">
                    <a href="{{ route('admin.reports.expiring-batches') }}" class="btn btn-sm btn-outline-warning">ดูรายงานล็อตครบกำหนดทั้งหมด</a>
                </div>
            </div>
        </div>
    </div>
@endif
