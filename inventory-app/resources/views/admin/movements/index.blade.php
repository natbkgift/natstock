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
    $activeTab = $prefill['active_tab'] ?? 'receive';
    $defaultForms = [
        'receive' => ['product_id' => null, 'qty' => null, 'expire_date' => null, 'note' => null],
        'issue' => ['product_id' => null, 'qty' => null, 'lot_no' => null, 'note' => null],
        'adjust' => ['product_id' => null, 'new_qty' => null, 'lot_no' => null, 'note' => null],
    ];
    $formValues = [
        'receive' => array_merge($defaultForms['receive'], $prefill['receive'] ?? []),
        'issue' => array_merge($defaultForms['issue'], $prefill['issue'] ?? []),
        'adjust' => array_merge($defaultForms['adjust'], $prefill['adjust'] ?? []),
    ];
    $typeLabels = ['receive' => 'รับเข้า', 'issue' => 'เบิกออก', 'adjust' => 'ปรับยอด'];
    $typeClasses = ['receive' => 'success', 'issue' => 'danger', 'adjust' => 'warning'];
@endphp

@section('content')
@can('create', App\Models\StockMovement::class)
<div class="card card-outline card-primary mb-4">
    <div class="card-header p-2">
        <ul class="nav nav-pills" id="movement-tabs">
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'receive' ? 'active' : '' }}" href="#tab-receive" data-toggle="tab">รับของ</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'issue' ? 'active' : '' }}" href="#tab-issue" data-toggle="tab">เบิกของ</a></li>
            <li class="nav-item"><a class="nav-link {{ $activeTab === 'adjust' ? 'active' : '' }}" href="#tab-adjust" data-toggle="tab">ปรับยอด</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade {{ $activeTab === 'receive' ? 'show active' : '' }}" id="tab-receive">
                @php
                    $receiveProduct = $formValues['receive']['product_id'];
                @endphp
                <form id="form-receive" class="movement-form" method="POST" data-action-template="{{ route('admin.products.receive', ['product' => '__PRODUCT__']) }}">
                    @csrf
                    <input type="hidden" name="form_type" value="receive">
                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="product_receive">เลือกสินค้า</label>
                            <select name="product_id" id="product_receive" class="form-control select2-product @if($activeTab === 'receive' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $receiveProduct === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'receive' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="expire_date_receive">วันหมดอายุ (ถ้ามี)</label>
                            <input type="date" name="expire_date" id="expire_date_receive" class="form-control @if($activeTab === 'receive' && $errors->has('expire_date')) is-invalid @endif" value="{{ $formValues['receive']['expire_date'] }}">
                            @if($activeTab === 'receive' && $errors->has('expire_date'))
                                <div class="invalid-feedback">{{ $errors->first('expire_date') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="qty_receive">จำนวน</label>
                            <input type="number" name="qty" id="qty_receive" class="form-control @if($activeTab === 'receive' && $errors->has('qty')) is-invalid @endif" min="0" step="1" value="{{ $formValues['receive']['qty'] }}" required>
                            @if($activeTab === 'receive' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="note_receive">หมายเหตุ</label>
                            <input type="text" name="note" id="note_receive" class="form-control @if($activeTab === 'receive' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $formValues['receive']['note'] }}">
                            @if($activeTab === 'receive' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-success">บันทึกรับเข้า</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade {{ $activeTab === 'issue' ? 'show active' : '' }}" id="tab-issue">
                @php
                    $issueProduct = $formValues['issue']['product_id'];
                    $issueLot = $formValues['issue']['lot_no'];
                @endphp
                <form id="form-issue" class="movement-form" method="POST" data-action-template="{{ route('admin.products.issue', ['product' => '__PRODUCT__']) }}">
                    @csrf
                    <input type="hidden" name="form_type" value="issue">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_issue">เลือกสินค้า</label>
                            <select name="product_id" id="product_issue" class="form-control select2-product @if($activeTab === 'issue' && $errors->has('product_id')) is-invalid @endif" data-batch-target="#lot_issue" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $issueProduct === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'issue' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lot_issue">ล็อต (เลือกได้หรือปล่อยว่างเพื่อให้ระบบเลือกอัตโนมัติ)</label>
                            <select name="lot_no" id="lot_issue" class="form-control select2-batch @if($activeTab === 'issue' && $errors->has('lot_no')) is-invalid @endif" data-mode="issue" data-product-input="#product_issue" data-selected="{{ $issueLot }}">
                                <option value="">ให้ระบบเลือกอัตโนมัติ</option>
                            </select>
                            @if($activeTab === 'issue' && $errors->has('lot_no'))
                                <div class="invalid-feedback d-block">{{ $errors->first('lot_no') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="qty_issue">จำนวน</label>
                            <input type="number" name="qty" id="qty_issue" class="form-control @if($activeTab === 'issue' && $errors->has('qty')) is-invalid @endif" min="1" step="1" value="{{ $formValues['issue']['qty'] }}" required>
                            @if($activeTab === 'issue' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="note_issue">หมายเหตุ</label>
                            <input type="text" name="note" id="note_issue" class="form-control @if($activeTab === 'issue' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $formValues['issue']['note'] }}">
                            @if($activeTab === 'issue' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="form-row">
                        @can('access-staff')
                            <div class="form-group col-md-4">
                                <button type="button" class="btn btn-outline-primary btn-block btn-open-batch-modal" data-product-select="#product_issue" data-batch-select="#lot_issue">+ สร้างล็อตใหม่</button>
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
                    $adjustProduct = $formValues['adjust']['product_id'];
                    $adjustLot = $formValues['adjust']['lot_no'];
                @endphp
                <form id="form-adjust" class="movement-form" method="POST" data-action-template="{{ route('admin.products.adjust', ['product' => '__PRODUCT__']) }}">
                    @csrf
                    <input type="hidden" name="form_type" value="adjust">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="product_adjust">เลือกสินค้า</label>
                            <select name="product_id" id="product_adjust" class="form-control select2-product @if($activeTab === 'adjust' && $errors->has('product_id')) is-invalid @endif" data-batch-target="#lot_adjust" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($productOptions as $product)
                                    <option value="{{ $product['id'] }}" data-qty="{{ number_format($product['qty']) }}" {{ (string) $adjustProduct === (string) $product['id'] ? 'selected' : '' }}>
                                        [{{ $product['sku'] }}] {{ $product['name'] }} (คงเหลือ {{ number_format($product['qty']) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'adjust' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-4">
                            <label for="lot_adjust">ล็อตที่ต้องการปรับ</label>
                            <select name="lot_no" id="lot_adjust" class="form-control select2-batch @if($activeTab === 'adjust' && $errors->has('lot_no')) is-invalid @endif" data-mode="adjust" data-product-input="#product_adjust" data-selected="{{ $adjustLot }}" required>
                                <option value="">-- เลือกล็อต --</option>
                            </select>
                            @if($activeTab === 'adjust' && $errors->has('lot_no'))
                                <div class="invalid-feedback d-block">{{ $errors->first('lot_no') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-2">
                            <label for="new_qty_adjust">จำนวนใหม่</label>
                            <input type="number" name="new_qty" id="new_qty_adjust" class="form-control @if($activeTab === 'adjust' && $errors->has('new_qty')) is-invalid @endif" min="0" step="1" value="{{ $formValues['adjust']['new_qty'] }}" required>
                            @if($activeTab === 'adjust' && $errors->has('new_qty'))
                                <div class="invalid-feedback">{{ $errors->first('new_qty') }}</div>
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
                                <button type="button" class="btn btn-outline-primary btn-block btn-open-batch-modal" data-product-select="#product_adjust" data-batch-select="#lot_adjust">+ สร้างล็อตใหม่</button>
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
                        <option value="receive" {{ $filters['type'] === 'receive' ? 'selected' : '' }}>รับเข้า</option>
                        <option value="issue" {{ $filters['type'] === 'issue' ? 'selected' : '' }}>เบิกออก</option>
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
                                    <div>{{ $movement->batch->lot_no }}</div>
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

        function formatBatchLabel(batch) {
            const expireText = batch.expire_date_th ? `หมดอายุ ${batch.expire_date_th}` : 'ไม่ระบุวันหมดอายุ';
            return `${batch.lot_no} — คงเหลือ ${new Intl.NumberFormat('th-TH').format(batch.qty ?? 0)} — ${expireText}`;
        }

        function filterBatchesByMode(batches, mode) {
            if (mode === 'issue') {
                return (batches || []).filter(batch => (batch.qty ?? 0) > 0);
            }

            return batches || [];
        }

        function renderBatchOptions($select, batches, selectedValue, mode) {
            const targetBatches = filterBatchesByMode(batches, mode);
            const fallbackValue = String($select.data('selected') ?? '');
            const currentValue = selectedValue ?? fallbackValue;
            const allowBlank = mode !== 'adjust';

            $select.empty();

            if (allowBlank) {
                const label = mode === 'issue' ? 'ให้ระบบเลือกอัตโนมัติ' : '-- เลือกล็อต --';
                $select.append(new Option(label, '', false, currentValue === ''));
            }

            targetBatches
                .slice()
                .sort((a, b) => (a.lot_no || '').localeCompare(b.lot_no || '', 'th'))
                .forEach(batch => {
                    const option = new Option(formatBatchLabel(batch), batch.lot_no, false, currentValue === batch.lot_no);
                    if (batch.expire_date_th) {
                        $(option).attr('data-expire', batch.expire_date_th);
                    }
                    $(option).attr('data-qty', batch.qty ?? 0);
                    $select.append(option);
                });

            $select.data('selected', currentValue || '');
            $select.trigger('change.select2');
        }

        function populateBatchSelect($select, productId, selectedValue) {
            const mode = String($select.data('mode') || 'adjust');
            const defaultValue = selectedValue ?? String($select.data('selected') ?? '');

            if (!productId) {
                renderBatchOptions($select, [], defaultValue, mode);
                return;
            }

            if (batchCache[productId]) {
                renderBatchOptions($select, batchCache[productId], defaultValue, mode);
                return;
            }

            const url = batchIndexUrlTemplate.replace('__PRODUCT__', productId);
            $select.prop('disabled', true);

            $.getJSON(url)
                .done(response => {
                    batchCache[productId] = response.results || [];
                    renderBatchOptions($select, batchCache[productId], defaultValue, mode);
                })
                .fail(() => {
                    renderBatchOptions($select, [], defaultValue, mode);
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
                const initialSelected = $batchSelect.data('selected') ?? $batchSelect.val();
                populateBatchSelect($batchSelect, $productSelect.val(), initialSelected);

                $productSelect.on('change', function () {
                    populateBatchSelect($batchSelect, $(this).val(), '');
                });
            }
        });

        $('.movement-form').each(function () {
            const $form = $(this);
            const actionTemplate = String($form.data('action-template') || '');
            const $productSelect = $form.find('.select2-product');

            $form.on('submit', function (event) {
                const productId = $productSelect.val();

                if (!productId) {
                    event.preventDefault();
                    alert('กรุณาเลือกสินค้าก่อนบันทึก');
                    return false;
                }

                if (actionTemplate.includes('__PRODUCT__')) {
                    const action = actionTemplate.replace('__PRODUCT__', productId);
                    $form.attr('action', action);
                }

                return true;
            });
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
                    batchCache[productId] = batchCache[productId].filter(item => item.lot_no !== batch.lot_no);
                    batchCache[productId].push(batch);

                    const $target = $(batchSelectSelector);
                    populateBatchSelect($target, productId, batch.lot_no);
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
