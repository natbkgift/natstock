<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function __construct(private readonly BatchResolver $batchResolver)
    {
    }

    public function receive(
        Product $product,
        int $qty,
        ?Carbon $expireDate = null,
        ?string $note = null
    ): StockMovement {
        if ($qty < 0) {
            throw ValidationException::withMessages([
                'qty' => 'จำนวนต้องไม่ติดลบ',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $expireDate, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);

            $batch = $this->batchResolver->resolveForReceive($productForUpdate, $expireDate, $note, true);
            $batchForUpdate = ProductBatch::query()
                ->where('product_id', $productForUpdate->id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $batchForUpdate->qty = $batchForUpdate->qty + $qty;
            $batchForUpdate->save();

            $this->refreshProductQty($productForUpdate);

            $movement = $this->createMovement(
                $productForUpdate->id,
                $batchForUpdate->id,
                'receive',
                $qty,
                $note,
                $actorId,
            );

            return $movement->loadMissing(['product', 'batch']);
        });
    }

    public function issue(
        Product $product,
        int $qty,
        ?string $lotNo = null,
        ?string $note = null
    ): StockMovement {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'จำนวนต้องมากกว่าศูนย์',
            ]);
        }

        return DB::transaction(function () use ($product, $qty, $lotNo, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);

            $batch = $this->batchResolver->resolveForIssue($productForUpdate, $lotNo);
            $batchForUpdate = ProductBatch::query()
                ->where('product_id', $productForUpdate->id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($batchForUpdate->qty < $qty) {
                throw ValidationException::withMessages([
                    'qty' => 'ล็อตนี้ไม่เพียงพอต่อการเบิก',
                ]);
            }

            $batchForUpdate->qty = $batchForUpdate->qty - $qty;
            $batchForUpdate->save();

            $this->refreshProductQty($productForUpdate);

            $movement = $this->createMovement(
                $productForUpdate->id,
                $batchForUpdate->id,
                'issue',
                $qty,
                $note,
                $actorId,
            );

            return $movement->loadMissing(['product', 'batch']);
        });
    }

    public function adjust(
        Product $product,
        string $lotNo,
        int $newQty,
        ?string $note = null
    ): StockMovement {
        if ($newQty < 0) {
            throw ValidationException::withMessages([
                'new_qty' => 'จำนวนใหม่ต้องไม่ติดลบ',
            ]);
        }

        return DB::transaction(function () use ($product, $lotNo, $newQty, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);

            $batch = $this->batchResolver->resolveForAdjust($productForUpdate, $lotNo);
            $batchForUpdate = ProductBatch::query()
                ->where('product_id', $productForUpdate->id)
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $oldQty = $batchForUpdate->qty;
            $batchForUpdate->qty = $newQty;
            $batchForUpdate->save();

            $this->refreshProductQty($productForUpdate);

            $delta = $newQty - $oldQty;
            if ($delta === 0) {
                $movementQty = 0;
            } else {
                $movementQty = abs($delta);
            }

            $movement = $this->createMovement(
                $productForUpdate->id,
                $batchForUpdate->id,
                'adjust',
                $movementQty,
                $this->buildAdjustNote($note, $delta),
                $actorId,
            );

            $movement->setAttribute('delta', $delta);

            return $movement->loadMissing(['product', 'batch']);
        });
    }

    private function currentActorId(): int
    {
        $user = Auth::user();

        if ($user === null) {
            throw ValidationException::withMessages([
                'auth' => 'ไม่พบผู้ใช้งานที่ลงชื่อเข้าใช้',
            ]);
        }

        return (int) $user->id;
    }

    private function refreshProductQty(Product $product): void
    {
        $total = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->sum('qty');

        $product->qty = (int) $total;
        $product->save();
    }

    private function buildAdjustNote(?string $note, int $delta): ?string
    {
        $note = trim((string) $note);
        $deltaText = 'Δ' . ($delta > 0 ? '+' : '') . $delta;

        if ($note === '') {
            return $deltaText;
        }

        return $note . ' ' . $deltaText;
    }

    private function createMovement(
        int $productId,
        int $batchId,
        string $type,
        int $qty,
        ?string $note,
        int $actorId,
    ): StockMovement {
        return StockMovement::create([
            'product_id' => $productId,
            'batch_id' => $batchId,
            'type' => $type,
            'qty' => $qty,
            'note' => $note,
            'actor_id' => $actorId,
            'happened_at' => now(),
        ]);
    }
}
