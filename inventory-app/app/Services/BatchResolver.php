<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BatchResolver
{
    public const UNSPECIFIED_TOKEN = '__UNSPECIFIED__';

    public function resolveForProduct(
        Product $product,
        ?string $lotNo,
        ?Carbon $expireDate,
        bool $createIfMissing = true
    ): ProductBatch {
        $normalized = $this->normalizeLotNo($lotNo);
        $normalizedExpireDate = $this->normalizeExpireDate($expireDate);

        if ($normalized === null) {
            return $this->resolveUnspecifiedBatch($product, $createIfMissing);
        }

        $batch = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('lot_no', $normalized)
            ->first();

        if ($batch !== null) {
            return $batch;
        }

        if (! $createIfMissing) {
            throw ValidationException::withMessages([
                'sub_sku' => 'ไม่พบล็อตย่อยนี้สำหรับสินค้า',
            ]);
        }

        return ProductBatch::create([
            'product_id' => $product->id,
            'lot_no' => $normalized,
            'expire_date' => $normalizedExpireDate,
            'qty' => 0,
            'is_active' => true,
        ]);
    }

    private function resolveUnspecifiedBatch(Product $product, bool $createIfMissing): ProductBatch
    {
        $lotNo = 'LOT-01';

        $batch = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('lot_no', $lotNo)
            ->first();

        if ($batch !== null) {
            return $batch;
        }

        if (! $createIfMissing) {
            throw ValidationException::withMessages([
                'sub_sku' => 'ไม่พบล็อตเริ่มต้นสำหรับสินค้านี้',
            ]);
        }

        return ProductBatch::create([
            'product_id' => $product->id,
            'lot_no' => $lotNo,
            'expire_date' => null,
            'qty' => 0,
            'is_active' => true,
        ]);
    }

    private function normalizeLotNo(?string $lotNo): ?string
    {
        $value = trim((string) $lotNo);

        if ($value === '' || $value === self::UNSPECIFIED_TOKEN) {
            return null;
        }

        if (mb_strlen($value) > 16) {
            $value = mb_substr($value, 0, 16);
        }

        return Str::of($value)->squish()->toString();
    }

    private function normalizeExpireDate(?Carbon $expireDate): ?Carbon
    {
        return $expireDate?->copy()->startOfDay();
    }
}
