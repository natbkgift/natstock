<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BatchResolver
{
    public function __construct(private readonly LotService $lotService)
    {
    }

    public function resolveForReceive(
        Product $product,
        ?Carbon $expireDate,
        ?string $note = null,
        bool $createIfMissing = true
    ): ProductBatch {
        $normalizedExpireDate = $expireDate?->copy()->startOfDay();

        if ($normalizedExpireDate !== null) {
            $existing = ProductBatch::query()
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->whereDate('expire_date', $normalizedExpireDate->toDateString())
                ->orderBy('received_at')
                ->orderBy('id')
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        if (! $createIfMissing) {
            throw ValidationException::withMessages([
                'lot_no' => 'ไม่พบล็อตที่ตรงกับวันหมดอายุที่ระบุ',
            ]);
        }

        $lotNo = $this->lotService->nextFor($product);

        return ProductBatch::create([
            'product_id' => $product->id,
            'lot_no' => $lotNo,
            'expire_date' => $normalizedExpireDate,
            'qty' => 0,
            'note' => $note !== null ? Str::of($note)->squish()->limit(255)->toString() : null,
            'received_at' => now(),
            'is_active' => true,
        ]);
    }

    public function resolveForIssue(Product $product, ?string $lotNo = null): ProductBatch
    {
        $normalized = $this->normalizeLotNo($lotNo);

        if ($normalized !== null) {
            $batch = ProductBatch::query()
                ->where('product_id', $product->id)
                ->where('lot_no', $normalized)
                ->where('is_active', true)
                ->first();

            if ($batch === null) {
                throw ValidationException::withMessages([
                    'lot_no' => 'ไม่พบล็อตนี้หรือถูกปิดใช้งาน',
                ]);
            }

            return $batch;
        }

        $batch = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->where('qty', '>', 0)
            ->orderByRaw('CASE WHEN expire_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expire_date')
            ->orderByRaw('CASE WHEN received_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('received_at')
            ->orderBy('id')
            ->first();

        if ($batch === null) {
            throw ValidationException::withMessages([
                'lot_no' => 'ไม่มีล็อตที่พร้อมให้เบิก',
            ]);
        }

        return $batch;
    }

    public function resolveForAdjust(Product $product, string $lotNo): ProductBatch
    {
        $normalized = $this->normalizeLotNo($lotNo);

        if ($normalized === null) {
            throw ValidationException::withMessages([
                'lot_no' => 'กรุณาเลือกล็อตที่ต้องการปรับยอด',
            ]);
        }

        $batch = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('lot_no', $normalized)
            ->where('is_active', true)
            ->first();

        if ($batch === null) {
            throw ValidationException::withMessages([
                'lot_no' => 'ไม่พบล็อตนี้สำหรับสินค้า',
            ]);
        }

        return $batch;
    }

    private function normalizeLotNo(?string $lotNo): ?string
    {
        $value = trim((string) $lotNo);

        if ($value === '') {
            return null;
        }

        return Str::of($value)->squish()->limit(32)->toString();
    }
}
