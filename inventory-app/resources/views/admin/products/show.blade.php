@extends('layouts.admin')

@section('title', 'รายละเอียดสินค้า')
@section('page_title', 'รายละเอียดสินค้า')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">สินค้า</a></li>
    <li class="breadcrumb-item active">{{ $product->sku }}</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header p-0 border-bottom-0">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tab-product-info" role="tab">ข้อมูลสินค้า</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tab-product-batches" role="tab">ล็อต (LOT)</a>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-product-info" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">SKU</dt>
                            <dd class="col-sm-8">{{ $product->sku }}</dd>

                            <dt class="col-sm-4">ชื่อสินค้า</dt>
                            <dd class="col-sm-8">{{ $product->name }}</dd>

                            <dt class="col-sm-4">หมวดหมู่</dt>
                            <dd class="col-sm-8">{{ $product->category->name ?? '-' }}</dd>

                            <dt class="col-sm-4">สถานะ</dt>
                            <dd class="col-sm-8">
                                <span class="badge badge-{{ $product->is_active ? 'success' : 'secondary' }}">{{ $product->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}</span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row mb-0">
                            <dt class="col-sm-5">คงเหลือรวม (ทุกล็อต)</dt>
                            <dd class="col-sm-7">{{ number_format($product->qtyCurrent()) }}</dd>

                            <dt class="col-sm-5">จุดสั่งซื้อซ้ำ</dt>
                            <dd class="col-sm-7">{{ number_format($product->reorder_point) }}</dd>

                            <dt class="col-sm-5">วันหมดอายุหลัก</dt>
                            <dd class="col-sm-7">
                                @if($product->expire_date)
                                    {{ $product->expire_date->locale('th')->translatedFormat('d M Y') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="tab-pane fade" id="tab-product-batches" role="tabpanel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">รายการล็อต</h4>
                    @can('access-staff')
                        <button type="button" class="btn btn-primary btn-sm" id="btn-open-product-batch-modal"><i class="fas fa-plus"></i> เพิ่มล็อตใหม่</button>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>LOT</th>
                                <th>วันหมดอายุ</th>
                                <th class="text-right">คงเหลือ</th>
                                <th>สถานะ</th>
                                <th class="text-right">เครื่องมือ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($product->batches as $batch)
                                <tr>
                                    <td>{{ $batch->lot_no }}</td>
                                    <td>
                                        @if($batch->expire_date)
                                            {{ $batch->expire_date->locale('th')->translatedFormat('d M Y') }}
                                        @else
                                            <span class="text-muted">ไม่ระบุ</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($batch->qty) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $batch->is_active ? 'success' : 'secondary' }}">{{ $batch->is_active ? 'ใช้งาน' : 'ปิดใช้งาน' }}</span>
                                    </td>
                                    <td class="text-right">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.movements.index', ['form_type' => 'receive', 'product_id' => $product->id, 'lot_no' => $batch->lot_no]) }}" class="btn btn-outline-success">รับเข้า</a>
                                            <a href="{{ route('admin.movements.index', ['form_type' => 'issue', 'product_id' => $product->id, 'lot_no' => $batch->lot_no]) }}" class="btn btn-outline-danger">เบิกออก</a>
                                            <a href="{{ route('admin.movements.index', ['form_type' => 'adjust', 'product_id' => $product->id, 'lot_no' => $batch->lot_no]) }}" class="btn btn-outline-warning text-warning">ปรับยอด</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">ยังไม่มีข้อมูลล็อตของสินค้านี้</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@can('access-staff')
<div class="modal fade" id="product-batch-modal" tabindex="-1" role="dialog" aria-labelledby="product-batch-modal-label" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="product-batch-modal-label">เพิ่มล็อตใหม่</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger d-none" id="product-batch-error"></div>
                <form id="product-batch-form">
                    @csrf
                    <div class="form-group">
                        <label for="product_modal_expire_date">วันหมดอายุ (ถ้ามี)</label>
                        <input type="date" id="product_modal_expire_date" name="expire_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="product_modal_note">หมายเหตุ</label>
                        <textarea id="product_modal_note" name="note" class="form-control" rows="2" placeholder="เพิ่มเติม (ถ้ามี)"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-primary" id="product-batch-save">บันทึกล็อต</button>
            </div>
        </div>
    </div>
</div>
@endcan
@endsection

@can('access-staff')
@push('scripts')
<script>
    $(function () {
        const storeUrl = '{{ route('admin.products.batches.store', $product) }}';
        const csrfToken = '{{ csrf_token() }}';

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            }
        });

        $('#btn-open-product-batch-modal').on('click', function () {
            $('#product-batch-error').addClass('d-none').text('');
            $('#product-batch-form')[0].reset();
            $('#product-batch-modal').modal('show');
        });

        $('#product-batch-modal').on('hidden.bs.modal', function () {
            $('#product-batch-error').addClass('d-none').text('');
            $('#product-batch-form')[0].reset();
        });

        $('#product-batch-save').on('click', function () {
            const $form = $('#product-batch-form');
            const payload = {
                expire_date: $form.find('input[name=expire_date]').val(),
                note: $form.find('textarea[name=note]').val(),
            };

            $('#product-batch-error').addClass('d-none').text('');

            $.post(storeUrl, payload)
                .done(() => {
                    window.location.reload();
                })
                .fail(xhr => {
                    let message = 'ไม่สามารถสร้างล็อตใหม่ได้';

                    if (xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = Object.values(xhr.responseJSON.errors).flat();
                        if (errors.length > 0) {
                            message = errors.join('\n');
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }

                    $('#product-batch-error').removeClass('d-none').text(message);
                });
        });
    });
</script>
@endpush
@endcan
