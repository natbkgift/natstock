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
    $activeTab = old('form_type', 'in');
    $oldValues = [
        'in' => [
            'product_id' => old('form_type') === 'in' ? old('product_id') : null,
            'qty' => old('form_type') === 'in' ? old('qty') : null,
            'note' => old('form_type') === 'in' ? old('note') : null,
        ],
        'out' => [
            'product_id' => old('form_type') === 'out' ? old('product_id') : null,
            'qty' => old('form_type') === 'out' ? old('qty') : null,
            'note' => old('form_type') === 'out' ? old('note') : null,
        ],
        'adjust' => [
            'product_id' => old('form_type') === 'adjust' ? old('product_id') : null,
            'target_qty' => old('form_type') === 'adjust' ? old('target_qty') : null,
            'note' => old('form_type') === 'adjust' ? old('note') : null,
        ],
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
                <form action="{{ route('admin.movements.store.in') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="in">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product_in">เลือกสินค้า</label>
                            <select name="product_id" id="product_in" class="form-control select2 @if($activeTab === 'in' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (string) $oldValues['in']['product_id'] === (string) $product->id ? 'selected' : '' }}>
                                        [{{ $product->sku }}] {{ $product->name }} (คงเหลือ {{ number_format($product->qty) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'in' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="qty_in">จำนวนรับเข้า</label>
                            <input type="number" name="qty" id="qty_in" class="form-control @if($activeTab === 'in' && $errors->has('qty')) is-invalid @endif" placeholder="ระบุจำนวน" min="1" value="{{ $oldValues['in']['qty'] }}" required>
                            @if($activeTab === 'in' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="note_in">หมายเหตุ</label>
                            <input type="text" name="note" id="note_in" class="form-control @if($activeTab === 'in' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $oldValues['in']['note'] }}">
                            @if($activeTab === 'in' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-success">บันทึกรับเข้า</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade {{ $activeTab === 'out' ? 'show active' : '' }}" id="tab-out">
                <form action="{{ route('admin.movements.store.out') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="out">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product_out">เลือกสินค้า</label>
                            <select name="product_id" id="product_out" class="form-control select2 @if($activeTab === 'out' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (string) $oldValues['out']['product_id'] === (string) $product->id ? 'selected' : '' }}>
                                        [{{ $product->sku }}] {{ $product->name }} (คงเหลือ {{ number_format($product->qty) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'out' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="qty_out">จำนวนเบิกออก</label>
                            <input type="number" name="qty" id="qty_out" class="form-control @if($activeTab === 'out' && $errors->has('qty')) is-invalid @endif" placeholder="ระบุจำนวน" min="1" value="{{ $oldValues['out']['qty'] }}" required>
                            @if($activeTab === 'out' && $errors->has('qty'))
                                <div class="invalid-feedback">{{ $errors->first('qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="note_out">หมายเหตุ</label>
                            <input type="text" name="note" id="note_out" class="form-control @if($activeTab === 'out' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $oldValues['out']['note'] }}">
                            @if($activeTab === 'out' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-danger">บันทึกการเบิกออก</button>
                    </div>
                </form>
            </div>
            <div class="tab-pane fade {{ $activeTab === 'adjust' ? 'show active' : '' }}" id="tab-adjust">
                <form action="{{ route('admin.movements.store.adjust') }}" method="POST">
                    @csrf
                    <input type="hidden" name="form_type" value="adjust">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="product_adjust">เลือกสินค้า</label>
                            <select name="product_id" id="product_adjust" class="form-control select2 @if($activeTab === 'adjust' && $errors->has('product_id')) is-invalid @endif" data-placeholder="ค้นหาสินค้า..." required>
                                <option value="">-- เลือกสินค้า --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ (string) $oldValues['adjust']['product_id'] === (string) $product->id ? 'selected' : '' }}>
                                        [{{ $product->sku }}] {{ $product->name }} (คงเหลือ {{ number_format($product->qty) }})
                                    </option>
                                @endforeach
                            </select>
                            @if($activeTab === 'adjust' && $errors->has('product_id'))
                                <div class="invalid-feedback d-block">{{ $errors->first('product_id') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="target_qty">จำนวนที่ควรเป็น</label>
                            <input type="number" name="target_qty" id="target_qty" class="form-control @if($activeTab === 'adjust' && $errors->has('target_qty')) is-invalid @endif" placeholder="ระบุจำนวนสุดท้าย" min="0" value="{{ $oldValues['adjust']['target_qty'] }}" required>
                            @if($activeTab === 'adjust' && $errors->has('target_qty'))
                                <div class="invalid-feedback">{{ $errors->first('target_qty') }}</div>
                            @endif
                        </div>
                        <div class="form-group col-md-3">
                            <label for="note_adjust">หมายเหตุ</label>
                            <input type="text" name="note" id="note_adjust" class="form-control @if($activeTab === 'adjust' && $errors->has('note')) is-invalid @endif" placeholder="เพิ่มเติม (ถ้ามี)" value="{{ $oldValues['adjust']['note'] }}">
                            @if($activeTab === 'adjust' && $errors->has('note'))
                                <div class="invalid-feedback">{{ $errors->first('note') }}</div>
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <button type="submit" class="btn btn-warning text-white">บันทึกการปรับยอด</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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
                                <span class="badge badge-{{ $typeClasses[$movement->type] ?? 'secondary' }}">{{ $typeLabels[$movement->type] ?? '-' }}</span>
                            </td>
                            <td class="text-right">
                                @php
                                    $displayQty = number_format($movement->qty);
                                    if ($movement->type === 'in') {
                                        $displayQty = '+' . $displayQty;
                                    } elseif ($movement->type === 'out') {
                                        $displayQty = '-' . $displayQty;
                                    } elseif ($movement->type === 'adjust') {
                                        $displayQty = 'Δ' . $displayQty;
                                    }
                                @endphp
                                {{ $displayQty }}
                            </td>
                            <td>{{ $movement->actor->name ?? '-' }}</td>
                            <td>{{ $movement->note ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">ยังไม่มีบันทึกการเคลื่อนไหว</td>
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
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%',
            language: {
                noResults: () => 'ไม่พบข้อมูล',
                searching: () => 'กำลังค้นหา...'
            }
        });
    });
</script>
@endpush
