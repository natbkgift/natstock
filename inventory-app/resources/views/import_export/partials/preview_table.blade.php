@php
    $labels = [
        'sku' => 'SKU',
        'qty' => 'จำนวน',
        'name' => 'ชื่อสินค้า',
        'category' => 'หมวดหมู่',
        'lot_no' => 'หมายเลขล็อต',
        'expire_date' => 'วันหมดอายุ (Y-m-d)',
        'reorder_point' => 'จุดสั่งซื้อซ้ำ',
        'note' => 'หมายเหตุ',
        'is_active' => 'สถานะใช้งาน',
    ];

    $ignoredKeys = ['cost_price', 'sale_price'];
    $headers = array_values(array_filter($preview['headers'] ?? [], fn ($header) => ! in_array($header, $ignoredKeys, true)));
@endphp

@if(empty($preview['rows']))
    <div class="text-muted">ไม่พบข้อมูลในไฟล์หรือข้อมูลทั้งหมดว่างเปล่า</div>
@else
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span>ทั้งหมด {{ $preview['total_rows'] }} แถว (พรีวิวสูงสุด 20 แถว)</span>
        @if(!empty($preview['ignored_columns']))
            <span class="badge badge-warning">คอลัมน์ราคาไม่ถูกใช้งาน: {{ implode(', ', $preview['ignored_columns']) }}</span>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-hover table-sm">
            <thead class="thead-light">
                <tr>
                    <th style="width: 70px;" class="text-center">แถวที่</th>
                    @foreach($headers as $header)
                        <th>{{ $labels[$header] ?? \Illuminate\Support\Str::of($header)->replace('_', ' ')->title() }}</th>
                    @endforeach
                    <th style="width: 220px;">สาเหตุ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($preview['rows'] as $row)
                    @php
                        $hasError = !empty($row['errors']);
                        $cells = $row['cells'] ?? [];
                    @endphp
                    <tr class="{{ $hasError ? 'table-danger' : '' }}">
                        <td class="text-center align-middle">{{ $row['row_number'] ?? '-' }}</td>
                        @foreach($headers as $header)
                            <td class="align-middle">{{ $cells[$header] ?? '' }}</td>
                        @endforeach
                        <td class="align-middle text-danger">
                            @if($hasError)
                                <ul class="mb-0 pl-3">
                                    @foreach($row['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-success">ผ่านการตรวจสอบเบื้องต้น</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
