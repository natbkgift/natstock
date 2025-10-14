<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\BatchResolver;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductBatchController extends Controller
{
    public function index(Product $product): JsonResponse
    {
        $this->authorize('viewAny', [ProductBatch::class, $product]);

        $batches = $product->batches()
            ->orderBy('lot_no')
            ->get()
            ->map(function (ProductBatch $batch) {
                return [
                    'id' => $batch->id,
                    'sub_sku' => $batch->lot_no,
                    'expire_date' => $batch->expire_date?->toDateString(),
                    'expire_date_th' => $batch->expire_date?->locale('th')->translatedFormat('d M Y'),
                    'qty' => (int) $batch->qty,
                    'is_active' => (bool) $batch->is_active,
                ];
            });

        return response()->json([
            'results' => $batches,
        ]);
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        $this->authorize('create', [ProductBatch::class, $product]);

        $validated = $request->validate(
            [
                'sub_sku' => ['required', 'string', 'max:16'],
                'expire_date' => ['nullable', 'date_format:Y-m-d'],
                'note' => ['nullable', 'string'],
            ],
            [
                'sub_sku.required' => 'กรุณาระบุรหัสล็อต',
                'sub_sku.string' => 'รหัสล็อตต้องเป็นข้อความ',
                'sub_sku.max' => 'รหัสล็อตต้องไม่เกิน 16 ตัวอักษร',
                'expire_date.date_format' => 'รูปแบบวันหมดอายุไม่ถูกต้อง (ใช้รูปแบบ YYYY-MM-DD)',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );

        $normalizedLotNo = $this->normalizeLotNo($validated['sub_sku']);

        if ($product->batches()->where('lot_no', $normalizedLotNo)->exists()) {
            throw ValidationException::withMessages([
                'sub_sku' => 'รหัสล็อตนี้ถูกใช้แล้วสำหรับสินค้า',
            ]);
        }

        $expireDate = null;
        if (! empty($validated['expire_date'])) {
            $expireDate = Carbon::createFromFormat('Y-m-d', $validated['expire_date'])->startOfDay();
        }

        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'lot_no' => $normalizedLotNo,
            'expire_date' => $expireDate,
            'qty' => 0,
            'note' => $validated['note'] ?? null,
            'is_active' => true,
        ]);

        $responseData = [
            'id' => $batch->id,
            'sub_sku' => $batch->lot_no,
            'expire_date' => $batch->expire_date?->toDateString(),
            'expire_date_th' => $batch->expire_date?->locale('th')->translatedFormat('d M Y'),
            'qty' => (int) $batch->qty,
            'is_active' => (bool) $batch->is_active,
        ];

        return response()->json([
            'message' => 'สร้างล็อตใหม่เรียบร้อย',
            'batch' => $responseData,
        ], 201);
    }

    private function normalizeLotNo(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw ValidationException::withMessages([
                'sub_sku' => 'รหัสล็อตต้องไม่เป็นค่าว่าง',
            ]);
        }

        if ($value === BatchResolver::UNSPECIFIED_TOKEN) {
            throw ValidationException::withMessages([
                'sub_sku' => 'รหัสล็อตนี้ถูกสงวนไว้สำหรับระบบ',
            ]);
        }

        if (mb_strlen($value) > 16) {
            $value = mb_substr($value, 0, 16);
        }

        return $value;
    }
}
