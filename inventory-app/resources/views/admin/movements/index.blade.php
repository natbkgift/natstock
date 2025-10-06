@extends('layouts.admin')

@section('title', 'เคลื่อนไหวสต็อก')
@section('page_title', 'บันทึกเคลื่อนไหวสต็อก')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item active">เคลื่อนไหวสต็อก</li>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@1.5.2/dist/select2-bootstrap4.min.css">
@endpush

@php
    $activeTab = $prefill['active_tab'] ?? 'in';
    $defaultForms = [
        'in' => ['product_id' => null, 'qty' => null, 'sub_sku' => null, 'expire_date' => null, 'note' => null],
        'out' => ['product_id' => null, 'qty' => null, 'sub_sku' => null, 'note' => null],
        'adjust' => ['product_id' => null, 'target_qty' => null, 'sub_sku' => null, 'note' => null],
    ];
    $formValues = [
        'in' => array_merge($defaultForms['in'], $prefill['in'] ?? []),
        'out' => array_merge($defaultForms['out'], $prefill['out'] ?? []),
        'adjust' => array_merge($defaultForms['adjust'], $prefill['adjust'] ?? []),
    ];
    $typeLabels = ['in' => 'รับเข้า', 'out' => 'เบิกออก', 'adjust' => 'ปรับยอด'];
    $typeClasses = ['in' => 'success', 'out' => 'danger', 'adjust' => 'warning'];
@endphp

@section('content')
@can('create', App\Models\StockMovement::class)
<div class="card card-outline card-primary mb-4">
    <div class="card-header p-2">
        <ul class="nav nav-pills" id="movement-tabs">
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'in' ? 'active' : '' }}" href="#tab-in" data-toggle="tab">รับเข้า</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'out' ? 'active' : '' }}" href="#tab-out" data-toggle="tab">เบิกออก</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'adjust' ? 'active' : '' }}" href="#tab-adjust" data-toggle="tab">ปรับยอด</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'in' ? 'show active' : '' }}" id="tab-in">
                @php
                    $selectedProductIn = $formValues['in']['product_id'];
                    $selectedBatchIn = $formValues['in']['sub_sku'];
                @endphp
                <form action="{{ route('admin.movements.store.in') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="in">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_in">เลือกสินค้า</label>
                            <select name="product_id" id="product_in" class="form-control select2-product @if($activeTab === 'in' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." data-batch-target="#batch_in" required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $selectedProductIn === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'in' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label for="batch_in">ล็อต (Sub-SKU)</label>
                            <select name="sub_sku" id="batch_in" class="form-control select2-batch @if($activeTab === 'in' && $errors->has('sub_sku')) is-invalid @endif" data-allow-unspecified="1" data-product-input="#product_in">
                                <option value="__UNSPECIFIED__" {{ empty($selectedBatchIn) || $selectedBatchIn === '__UNSPECIFIED__' ? 'selected' : '' }}>ไม่ระบุ (UNSPECIFIED)</option>
                                @if($selectedProductIn && isset($initialBatchOptions[$selectedProductIn]))
                                    @foreach($initialBatchOptions[$selectedProductIn] as $batch)
                                        <option value="{{ $batch['sub_sku'] }}" data-expire="{{ $batch['expire_date_th'] }}" {{ (string) $selectedBatchIn === (string) $batch['sub_sku'] ? 'selected' : '' }}>
                                            {{ $batch['label'] }} (คงเหลือ {{ number_format($batch['qty']) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="form-text text-muted">หากไม่เลือก ระบบจะจัดไปที่ล็อต UNSPECIFIED</small>
                            @if($activeTab === 'in' && $errors->has('sub_sku'))
                                <div class="invalid-feedback d-block">{{ $errors->first('sub_sku') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="expire_date_in">วันหมดอายุ (ถ้ามี)</label>
                            <input type="date" name="expire_date" id="expire_date_in" class="form-control @if($activeTab === 'in' && $errors->has('expire_date')) is-invalid @endif" value="{{ $formValues['in']['expire_date'] }}">
                            @if($activeTab === 'in' && $errors->has('expire_date'))
                                <div class="invalid-feedback">{{ $errors->first('expire_date') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="qty_in">จำนวนรับเข้า</label>
                            <input type="number" name="qty" id="qty_in" class="form-control @if($activeTab === 'in' && $errors->has('qty')) is-invalid @endif" placeholder="ระบุจำนวน" min="1" value="{{ $formValues['in']['qty'] }}" required>
                            @if($activeTab === 'in' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label for="note_in">หมายเหตุ</label>
                            <input type="text" name="note" id="note_in" class="form-control @if($activeTab === 'in' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $formValues['in']['note'] }}">
                            @if($activeTab === 'in' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                        @can('access-staff')
                            <div class="form-group col-md-4 d-flex align-items-end">
                                <button type="button" class="btn btn-outline-primary btn-block btn-open-batch-modal" data-product-select="#product_in" data-batch-select="#batch_in">+ สร้างล็อตใหม่</button>
                            </div>
                        @endcan
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-success">บันทึกรับเข้า</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade {{ $activeTab === 'out' ? 'show active' : '' }}" id="tab-out">
                @php
                    $selectedProductOut = $formValues['out']['product_id'];
                    $selectedBatchOut = $formValues['out']['sub_sku'];
                @endphp
                <form action="{{ route('admin.movements.store.out') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="out">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_out">เลือกสินค้า</label>
                            <select name="product_id" id="product_out" class="form-control select2-product @if($activeTab === 'out' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." data-batch-target="#batch_out" required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $selectedProductOut === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'out' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label for="batch_out">ล็อต (Sub-SKU)</label>
                            <select name="sub_sku" id="batch_out" class="form-control select2-batch @if($activeTab === 'out' && $errors->has('sub_sku')) is-invalid @endif" data-allow-unspecified="1" data-product-input="#product_out">
                                <option value="__UNSPECIFIED__" {{ empty($selectedBatchOut) || $selectedBatchOut === '__UNSPECIFIED__' ? 'selected' : '' }}>ไม่ระบุ (UNSPECIFIED)</option>
                                @if($selectedProductOut && isset($initialBatchOptions[$selectedProductOut]))
                                    @foreach($initialBatchOptions[$selectedProductOut] as $batch)
                                        <option value="{{ $batch['sub_sku'] }}" data-expire="{{ $batch['expire_date_th'] }}" {{ (string) $selectedBatchOut === (string) $batch['sub_sku'] ? 'selected' : '' }}>
                                            {{ $batch['label'] }} (คงเหลือ {{ number_format($batch['qty']) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="form-text text-muted">ถ้าไม่เลือกจะถือเป็นล็อต UNSPECIFIED</small>
                            @if($activeTab === 'out' && $errors->has('sub_sku'))
                                <div class="invalid-feedback d-block">{{ $errors->first('sub_sku') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="qty_out">จำนวนเบิกออก</label>
                            <input type="number" name="qty" id="qty_out" class="form-control @if($activeTab === 'out' && $errors->has('qty')) is-invalid @endif" placeholder="ระบุจำนวน" min="1" value="{{ $formValues['out']['qty'] }}" required>
                            @if($activeTab === 'out' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="note_out">หมายเหตุ</label>
                            <input type="text" name="note" id="note_out" class="form-control @if($activeTab === 'out' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $formValues['out']['note'] }}">
                            @if($activeTab === 'out' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-row">
                        @can('access-staff')
                            <div class="form-group col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-block btn-open-batch-modal" data-product-select="#product_out" data-batch-select="#batch_out">+ สร้างล็อตใหม่</button>
                            </div>
                        @endcan
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-danger">บันทึกการเบิกออก</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade {{ $activeTab === 'adjust' ? 'show active' : '' }}" id="tab-adjust">
                @php
                    $selectedProductAdjust = $formValues['adjust']['product_id'];
                    $selectedBatchAdjust = $formValues['adjust']['sub_sku'];
                @endphp
                <form action="{{ route('admin.movements.store.adjust') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="adjust">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_adjust">เลือกสินค้า</label>
                            <select name="product_id" id="product_adjust" class="form-control select2-product @if($activeTab === 'adjust' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." data-batch-target="#batch_adjust" required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $selectedProductAdjust === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'adjust' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label for="batch_adjust">ล็อต (Sub-SKU)</label>
                            <select name="sub_sku" id="batch_adjust" class="form-control select2-batch @if($activeTab === 'adjust' && $errors->has('sub_sku')) is-invalid @endif" data-allow-unspecified="1" data-product-input="#product_adjust" required>
                                <option value="__UNSPECIFIED__" {{ empty($selectedBatchAdjust) || $selectedBatchAdjust === '__UNSPECIFIED__' ? 'selected' : '' }}>ไม่ระบุ (UNSPECIFIED)</option>
                                @if($selectedProductAdjust && isset($initialBatchOptions[$selectedProductAdjust]))
                                    @foreach($initialBatchOptions[$selectedProductAdjust] as $batch)
                                        <option value="{{ $batch['sub_sku'] }}" data-expire="{{ $batch['expire_date_th'] }}" {{ (string) $selectedBatchAdjust === (string) $batch['sub_sku'] ? 'selected' : '' }}>
                                            {{ $batch['label'] }} (คงเหลือ {{ number_format($batch['qty']) }})
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            @if($activeTab === 'adjust' && $errors->has('sub_sku'))
                                <div class="invalid-feedback d-block">{{ $errors->first('sub_sku') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="target_qty">จำนวนที่ควรเป็น</label>
                            <input type="number" name="target_qty" id="target_qty" class="form-control @if($activeTab === 'adjust' && $errors->has('target_qty')) is-invalid @endif" placeholder="ระบุจำนวนสุดท้าย" min="0" value="{{ $formValues['adjust']['target_qty'] }}" required>
                            @if($activeTab === 'adjust' && $errors->has('target_qty'))
                                <div class="invalid-feedback">{{ $errors->first('target_qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="note_adjust">หมายเหตุ</label>
                            <input type="text" name="note" id="note_adjust" class="form-control @if($activeTab === 'adjust' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $formValues['adjust']['note'] }}">
                            @if($activeTab === 'adjust' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-row">
                        @can('access-staff')
                            <div class="form-group col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-block btn-open-batch-modal" data-product-select="#product_adjust" data-batch-select="#batch_adjust">+ สร้างล็อตใหม่</button>
                            </div>
                        @endcan
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-warning text-white">บันทึกการปรับยอด</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@can('access-staff')
<div class="modal fade" id="modal-create-batch" tabindex="-1" role="dialog" aria-labelledby="modal-create-batch-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-create-batch-label">สร้างล็อตใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="batch-modal-error"></div>
                <form id="form-create-batch">
                    @csrf
                    <input type="hidden" name="product_id" value="">
                    <input type="hidden" name="batch_select" value="">
                    <div class="form-group">
                        <label for="modal_sub_sku">รหัสล็อต (Sub-SKU)</label>
                        <input type="text" id="modal_sub_sku" name="sub_sku" class="form-control" maxlength="64" required>
                    </div>
                    <div class="form-group">
                        <label for="modal_expire_date">วันหมดอายุ (ถ้ามี)</label>
                        <input type="date" id="modal_expire_date" name="expire_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="modal_note">หมายเหตุ</label>
                        <textarea id="modal_note" name="note" class="form-control" rows="2" placeholder="เพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ยกเลิก</button>
                <button type="button" class="btn btn-primary" id="btn-save-batch">บันทึกล็อต</button>
            </div>
        </div>
    </div>
</div>
@endcan
@else
<div class="alert alert-info">สิทธิ์ของคุณสามารถดูข้อมูลเคลื่อนไหวได้เท่านั้น</div>
@endcan

<div class="card">
    <div class="card-header">
        <h3 class="card-title mb-0">ประวัติการเคลื่อนไหว</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.movements.index') }}" class="mb-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-3">
                    <label for="date_from">จากวันที่</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="form-group col-md-3">
                    <label for="date_to">ถึงวันที่</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="form-group col-md-2">
                    <label for="type">ประเภท</label>
                    <select name="type" id="type" class="form-control">
                        <option value="">ทั้งหมด</option>
                        <option value="in" {{ $filters['type'] === 'in' ? 'selected' : '' }}>รับเข้า</option>
                        <option value="out" {{ $filters['type'] === 'out' ? 'selected' : '' }}>เบิกออก</option>
                        <option value="adjust" {{ $filters['type'] === 'adjust' ? 'selected' : '' }}>ปรับยอด</option>
                    </select>
                </div>
                <div class="form-group col-md-4">
                    <label for="search">ค้นหา SKU/ชื่อสินค้า</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="ระบุคำค้น" value="{{ $filters['search'] }}">
                </div>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-outline-primary mr-2">กรองข้อมูล</button>
                <a href="{{ route('admin.movements.index') }}" class="btn btn-outline-secondary">ล้างตัวกรอง</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>วันที่/เวลา</th>
                        <th>สินค้า</th>
                        <th>ล็อต</th>
                        <th>ประเภท</th>
                        <th class="text-right">จำนวน</th>
                        <th>ผู้ปฏิบัติ</th>
                        <th>หมายเหตุ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $movement)
                        <tr>
                            <td>{{ $movement->happened_at?->format('d/m/Y H:i') }}</td>
                            <td>
                                <strong>[{{ $movement->product->sku ?? '-' }}]</strong>
                                <div>{{ $movement->product->name ?? '-' }}</div>
                            </td>
                            <td>
                                @if($movement->batch)
                                    <div>{{ $movement->batch->sub_sku }}</div>
                                    @if($movement->batch->expire_date)
                                        <small class="text-muted">หมดอายุ {{ $movement->batch->expire_date->locale('th')->translatedFormat('d M Y') }}</small>
                                    @else
                                        <small class="text-muted">ไม่ระบุวันหมดอายุ</small>
                                    @endif
                                @else
                                    <span class="badge badge-secondary">ไม่มีข้อมูลล็อต (legacy)</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $typeClasses[$movement->type] ?? 'secondary' }}">{{ $typeLabels[$movement->type] ?? '-' }}</span>
                            </td>
                            <td class="text-right">{{ $movement->formatted_qty }}</td>
                            <td>{{ $movement->actor->name ?? '-' }}</td>
                            <td>
                                {{ $movement->note ?? '-' }}
                                @if(! $movement->batch)
                                    <br><small class="text-muted">เชื่อมต่อแบบเดิม (ไม่มีการระบุล็อต)</small>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">ยังไม่มีบันทึกการเคลื่อนไหว</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-end">
            {{ $movements->links() }}
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function () {
        const UNSPECIFIED_VALUE = '__UNSPECIFIED__';
        const batchCache = @json($initialBatchOptions);
        const batchIndexUrlTemplate = @json(route('admin.products.batches.index', ['product' => '__PRODUCT__']));
        const batchStoreUrlTemplate = @json(route('admin.products.batches.store', ['product' => '__PRODUCT__']));
        const csrfToken = '{{ csrf_token() }}';

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            }
        });

        function formatProductOption(product) {
            if (!product.id) {
                return product.text;
            }

            const qty = product.qty ?? $(product.element).data('qty');
            const container = $('<div>').addClass('d-flex justify-content-between align-items-center');
            $('<span>').text(product.text ?? '').appendTo(container);
            $('<small>').addClass('text-muted').text(`คงเหลือ ${qty ?? '-'}`).appendTo(container);

            return container;
        }

        function formatProductSelection(product) {
            if (!product.id) {
                return product.text;
            }

            const qty = product.qty ?? $(product.element).data('qty');
            return `${product.text ?? ''} (คงเหลือ ${qty ?? '-'})`;
        }

        function renderBatchOptions($select, batches, selectedValue) {
            const allowUnspecified = String($select.data('allow-unspecified')) === '1';
            let targetValue = selectedValue ?? null;

            $select.empty();

            if (allowUnspecified) {
                const isSelected = !targetValue || targetValue === UNSPECIFIED_VALUE;
                const option = new Option('ไม่ระบุ (UNSPECIFIED)', UNSPECIFIED_VALUE, false, isSelected);
                $select.append(option);
                if (!targetValue) {
                    targetValue = UNSPECIFIED_VALUE;
                }
            }

            (batches || [])
                .slice()
                .sort((a, b) => (a.sub_sku || '').localeCompare(b.sub_sku || '', 'th'))
                .forEach(batch => {
                    const label = `${batch.label} (คงเหลือ ${new Intl.NumberFormat('th-TH').format(batch.qty ?? 0)})`;
                    const option = new Option(label, batch.sub_sku, false, targetValue === batch.sub_sku);
                    if (batch.expire_date_th) {
                        $(option).attr('data-expire', batch.expire_date_th);
                    }
                    $select.append(option);
                });

            if (targetValue) {
                $select.val(targetValue);
            }

            $select.trigger('change.select2');
        }

        function populateBatchSelect($select, productId, selectedValue) {
            if (!productId) {
                renderBatchOptions($select, [], selectedValue);
                return;
            }

            if (batchCache[productId]) {
                renderBatchOptions($select, batchCache[productId], selectedValue);
                return;
            }

            const url = batchIndexUrlTemplate.replace('__PRODUCT__', productId);
            $select.prop('disabled', true);

            $.getJSON(url)
                .done(response => {
                    batchCache[productId] = response.results || [];
                    renderBatchOptions($select, batchCache[productId], selectedValue);
                })
                .fail(() => {
                    renderBatchOptions($select, [], selectedValue);
                })
                .always(() => {
                    $select.prop('disabled', false);
                });
        }

        $('.select2-batch').each(function () {
            $(this).select2({
                theme: 'bootstrap4',
                width: '100%',
                language: {
                    noResults: () => 'ไม่พบล็อต',
                    searching: () => 'กำลังค้นหา...',
                },
            });
        });

        $('.select2-product').each(function () {
            const $productSelect = $(this);
            const batchTarget = $productSelect.data('batch-target');

            $productSelect.select2({
                theme: 'bootstrap4',
                width: '100%',
                language: {
                    noResults: () => 'ไม่พบข้อมูล',
                    searching: () => 'กำลังค้นหา...',
                },
                minimumInputLength: 1,
                ajax: {
                    url: '{{ route('admin.movements.products.search') }}',
                    dataType: 'json',
                    delay: 300,
                    data: params => ({
                        q: params.term || '',
                    }),
                    processResults: data => ({
                        results: data.results,
                    }),
                    cache: true,
                },
                templateResult: formatProductOption,
                templateSelection: formatProductSelection,
            });

            if (batchTarget) {
                const $batchSelect = $(batchTarget);
                const productId = $productSelect.val();
                const selectedBatch = $batchSelect.val();

                populateBatchSelect($batchSelect, productId, selectedBatch);

                $productSelect.on('change', function () {
                    const newProductId = $(this).val();
                    populateBatchSelect($batchSelect, newProductId, UNSPECIFIED_VALUE);
                });
            }
        });

        $('.btn-open-batch-modal').on('click', function () {
            const productSelectSelector = $(this).data('product-select');
            const batchSelectSelector = $(this).data('batch-select');
            const productId = $(productSelectSelector).val();

            if (!productId) {
                alert('กรุณาเลือกสินค้าก่อนสร้างล็อต');
                return;
            }

            $('#batch-modal-error').addClass('d-none').html('');
            $('#form-create-batch')[0].reset();
            $('#form-create-batch input[name=product_id]').val(productId);
            $('#form-create-batch input[name=batch_select]').val(batchSelectSelector);
            $('#modal-create-batch').modal('show');
        });

        $('#modal-create-batch').on('hidden.bs.modal', function () {
            $('#batch-modal-error').addClass('d-none').html('');
            $('#form-create-batch')[0].reset();
        });

        $('#btn-save-batch').on('click', function () {
            const $form = $('#form-create-batch');
            const productId = $form.find('input[name=product_id]').val();
            const batchSelectSelector = $form.find('input[name=batch_select]').val();
            const url = batchStoreUrlTemplate.replace('__PRODUCT__', productId);

            const payload = {
                sub_sku: $form.find('input[name=sub_sku]').val(),
                expire_date: $form.find('input[name=expire_date]').val(),
                note: $form.find('textarea[name=note]').val(),
            };

            $('#batch-modal-error').addClass('d-none').html('');

            $.post(url, payload)
                .done(response => {
                    $('#modal-create-batch').modal('hide');
                    if (!batchCache[productId]) {
                        batchCache[productId] = [];
                    }

                    const batch = response.batch;
                    batchCache[productId] = batchCache[productId].filter(item => item.sub_sku !== batch.sub_sku);
                    batchCache[productId].push(batch);

                    const $target = $(batchSelectSelector);
                    populateBatchSelect($target, productId, batch.sub_sku);
                })
                .fail(xhr => {
                    let message = 'ไม่สามารถสร้างล็อตใหม่ได้';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        if (errors.length > 0) {
                            message = errors.join('<br>');
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    $('#batch-modal-error').removeClass('d-none').html(message);
                });
        });
    });
</script>
@endpush
