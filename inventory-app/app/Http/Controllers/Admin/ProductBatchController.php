<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\LotService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBatchController extends Controller
{
    public function __construct(private readonly LotService $lotService)
    {
    }

    public function index(Product $product): JsonResponse
    {
        $this->authorize('viewAny', [ProductBatch::class, $product]);

        $batches = $product->batches()
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN expire_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('expire_date')
            ->orderBy('lot_no')
            ->get()
            ->map(function (ProductBatch $batch) {
                return [
                    'id' => $batch->id,
                    'lot_no' => $batch->lot_no,
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
                'expire_date' => ['nullable', 'date_format:Y-m-d'],
                'note' => ['nullable', 'string'],
            ],
            [
                'expire_date.date_format' => 'รูปแบบวันหมดอายุไม่ถูกต้อง (ใช้รูปแบบ YYYY-MM-DD)',
                'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            ]
        );

        $expireDate = null;
        if (! empty($validated['expire_date'])) {
            $expireDate = Carbon::createFromFormat('Y-m-d', $validated['expire_date'])->startOfDay();
        }

        $lotNo = $this->lotService->nextFor($product);

        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'lot_no' => $lotNo,
            'expire_date' => $expireDate,
            'qty' => 0,
            'note' => $validated['note'] ?? null,
            'is_active' => true,
        ]);

        $responseData = [
            'id' => $batch->id,
            'lot_no' => $batch->lot_no,
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
}
