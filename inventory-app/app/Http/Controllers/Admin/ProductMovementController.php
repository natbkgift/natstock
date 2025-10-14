<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Services\AuditLogger;
use App\Services\StockMovementService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductMovementController extends Controller
{
    public function __construct(
        private readonly StockMovementService $stockMovementService,
        private readonly AuditLogger $auditLogger
    ) {
    }

    public function storeReceive(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $request->validate(
            [
                'qty' => ['required', 'integer', 'min:0'],
                'expire_date' => ['nullable', 'date_format:Y-m-d'],
                'note' => ['nullable', 'string'],
            ],
            [
                'qty.required' => 'กรุณาระบุจำนวน',
                'qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'qty.min' => 'จำนวนต้องไม่ติดลบ',
                'expire_date.date_format' => 'รูปแบบวันหมดอายุไม่ถูกต้อง (ใช้รูปแบบ YYYY-MM-DD)',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ],
        );

        $expireDate = $validated['expire_date'] !== null
            ? Carbon::createFromFormat('Y-m-d', $validated['expire_date'])->startOfDay()
            : null;

        $movement = $this->stockMovementService->receive(
            $product,
            (int) $validated['qty'],
            $expireDate,
            $validated['note'] ?? null,
        );

        $this->auditLogger->log(
            'stock.receive',
            'รับของเข้าคลัง',
            $this->buildReceiveContext($movement, (int) $validated['qty']),
            $movement,
            $request->user(),
        );

        return $this->redirectAfterAction($request, 'receive', $product, $movement->batch)
            ->with('status', 'รับของเข้าคลังเรียบร้อย');
    }

    public function storeIssue(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $request->validate(
            [
                'qty' => ['required', 'integer', 'min:1'],
                'lot_no' => ['nullable', 'string'],
                'note' => ['nullable', 'string'],
            ],
            [
                'qty.required' => 'กรุณาระบุจำนวน',
                'qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'qty.min' => 'จำนวนต้องมากกว่าศูนย์',
                'lot_no.string' => 'เลือกรายการล็อตไม่ถูกต้อง',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ],
        );

        $movement = $this->stockMovementService->issue(
            $product,
            (int) $validated['qty'],
            $validated['lot_no'] ?? null,
            $validated['note'] ?? null,
        );

        $this->auditLogger->log(
            'stock.issue',
            'เบิกสินค้าออกจากคลัง',
            $this->buildIssueContext($movement, (int) $validated['qty']),
            $movement,
            $request->user(),
        );

        return $this->redirectAfterAction($request, 'issue', $product, $movement->batch)
            ->with('status', 'บันทึกการเบิกสินค้าเรียบร้อย');
    }

    public function storeAdjust(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('create', StockMovement::class);

        $validated = $request->validate(
            [
                'lot_no' => ['required', 'string'],
                'new_qty' => ['required', 'integer', 'min:0'],
                'note' => ['nullable', 'string'],
            ],
            [
                'lot_no.required' => 'กรุณาเลือกล็อตที่จะปรับยอด',
                'lot_no.string' => 'เลือกรายการล็อตไม่ถูกต้อง',
                'new_qty.required' => 'กรุณาระบุจำนวนใหม่',
                'new_qty.integer' => 'จำนวนต้องเป็นตัวเลขจำนวนเต็ม',
                'new_qty.min' => 'จำนวนต้องไม่ติดลบ',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ],
        );

        $movement = $this->stockMovementService->adjust(
            $product,
            $validated['lot_no'],
            (int) $validated['new_qty'],
            $validated['note'] ?? null,
        );

        $this->auditLogger->log(
            'stock.adjust',
            'ปรับยอดสต็อก',
            $this->buildAdjustContext($movement, (int) $validated['new_qty']),
            $movement,
            $request->user(),
        );

        $delta = $movement->getAttribute('delta');
        $status = $delta === 0
            ? 'ปรับยอดแล้ว (ไม่มีการเปลี่ยนแปลง)'
            : sprintf('ปรับยอดเรียบร้อย (Δ%s)', $delta > 0 ? '+' . $delta : $delta);

        return $this->redirectAfterAction($request, 'adjust', $product, $movement->batch)
            ->with('status', $status);
    }

    private function redirectAfterAction(Request $request, string $formType, Product $product, ?ProductBatch $batch): RedirectResponse
    {
        $query = [
            'form_type' => $formType,
            'product_id' => $product->getKey(),
        ];

        if ($batch !== null) {
            $query['lot_no'] = $batch->lot_no;
        }

        return redirect()->route('admin.movements.index', $query);
    }

    private function buildReceiveContext(StockMovement $movement, int $qty): array
    {
        $batch = $movement->batch;
        $product = $movement->product;
        $batchAfter = (int) ($batch?->qty ?? 0);
        $productAfter = (int) ($product?->qty ?? 0);

        return [
            'product_sku' => $product?->sku,
            'product_name' => $product?->name,
            'qty' => $qty,
            'batch_lot_no' => $batch?->lot_no,
            'batch_before_qty' => max(0, $batchAfter - $qty),
            'batch_after_qty' => $batchAfter,
            'before_qty' => max(0, $productAfter - $qty),
            'after_qty' => $productAfter,
        ];
    }

    private function buildIssueContext(StockMovement $movement, int $qty): array
    {
        $batch = $movement->batch;
        $product = $movement->product;
        $batchAfter = (int) ($batch?->qty ?? 0);
        $productAfter = (int) ($product?->qty ?? 0);

        return [
            'product_sku' => $product?->sku,
            'product_name' => $product?->name,
            'qty' => $qty,
            'batch_lot_no' => $batch?->lot_no,
            'batch_before_qty' => $batchAfter + $qty,
            'batch_after_qty' => $batchAfter,
            'before_qty' => $productAfter + $qty,
            'after_qty' => $productAfter,
        ];
    }

    private function buildAdjustContext(StockMovement $movement, int $newQty): array
    {
        $batch = $movement->batch;
        $product = $movement->product;
        $delta = (int) ($movement->getAttribute('delta') ?? 0);
        $batchAfter = (int) ($batch?->qty ?? $newQty);
        $productAfter = (int) ($product?->qty ?? 0);

        return [
            'product_sku' => $product?->sku,
            'product_name' => $product?->name,
            'delta' => $delta,
            'batch_lot_no' => $batch?->lot_no,
            'batch_before_qty' => $newQty - $delta,
            'batch_after_qty' => $batchAfter,
            'before_qty' => $productAfter - $delta,
            'after_qty' => $productAfter,
        ];
    }
}
