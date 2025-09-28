@extends('layouts.admin')

@section('title', 'รายงานมูลค่าสต็อก')
@section('page_title', 'รายงานมูลค่าสต็อก')
@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">รายงาน</a></li>
    <li class="breadcrumb-item active">รายงานมูลค่าสต็อก</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">ภาพรวมมูลค่าสินค้าคงคลัง</h3>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
            <i class="fas fa-file-csv mr-1"></i> ส่งออก CSV
        </a>
    </div>
    <div class="card-body">
        <div class="mb-3">
            <div class="d-flex flex-wrap align-items-center">
                @foreach($summary as $item)
                    <span class="badge badge-light border mr-2 mb-2">{{ $item }}</span>
                @endforeach
            </div>
        </div>
        <form method="GET" class="mb-4">
            @include('admin.reports.partials.common-filters')
            <div class="text-right">
                <button type="submit" class="btn btn-primary">กรองข้อมูล</button>
                <a href="{{ route('admin.reports.valuation') }}" class="btn btn-secondary">ล้างตัวกรอง</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="thead-light">
                    <tr>
                        <th>SKU</th>
                        <th>ชื่อสินค้า</th>
                        <th>หมวดหมู่</th>
                        <th class="text-right">ราคาทุน (ต่อหน่วย)</th>
                        <th class="text-right">คงเหลือ (qty)</th>
                        <th class="text-right">มูลค่ารวม</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        @php
                            $costPrice = number_format((float) $product->cost_price, 2, '.', ',');
                            $qty = (int) $product->qty;
                            $total = number_format($qty * (float) $product->cost_price, 2, '.', ',');
                        @endphp
                        <tr>
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->category->name ?? '-' }}</td>
                            <td class="text-right">{{ $costPrice }}</td>
                            <td class="text-right">{{ number_format($qty) }}</td>
                            <td class="text-right font-weight-bold">{{ $total }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">ไม่มีข้อมูลตามตัวกรองที่เลือก</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(($products instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $products->count() : $products->count()) > 0)
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-right">ยอดรวม</th>
                            <th class="text-right text-success">{{ number_format($totalValue, 2, '.', ',') }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        @if(method_exists($products, 'links'))
            <div class="mt-3">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
