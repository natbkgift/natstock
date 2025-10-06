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

    /**
     * @return array{movement: StockMovement, product_before: int, product_after: int, batch_before: int, batch_after: int, batch: ProductBatch, product: Product}
     */
    public function receive(
        Product $product,
        int $qty,
        ?string $subSku,
        ?Carbon $expireDate,
        ?string $note
    ): array {
        $this->ensurePositiveQuantity($qty);

        return DB::transaction(function () use ($product, $qty, $subSku, $expireDate, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);
            $productBefore = $productForUpdate->qtyCurrent();

            $batch = $this->batchResolver->resolveForProduct($productForUpdate, $subSku, $expireDate);
            $batchForUpdate = ProductBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $batchBefore = $batchForUpdate->qty;
            $batchForUpdate->qty = $batchBefore + $qty;
            $batchForUpdate->save();

            $productAfter = $this->refreshProductQty($productForUpdate);
            $batchAfter = $batchForUpdate->qty;

            $movement = StockMovement::create([
                'product_id' => $productForUpdate->id,
                'batch_id' => $batchForUpdate->id,
                'type' => 'in',
                'qty' => $qty,
                'note' => $note,
                'actor_id' => $actorId,
                'happened_at' => now(),
            ]);

            return [
                'movement' => $movement,
                'product_before' => $productBefore,
                'product_after' => $productAfter,
                'batch_before' => $batchBefore,
                'batch_after' => $batchAfter,
                'batch' => $batchForUpdate->fresh(),
                'product' => $productForUpdate->fresh(),
            ];
        });
    }

    /**
     * @return array{movement: StockMovement, product_before: int, product_after: int, batch_before: int, batch_after: int, batch: ProductBatch, product: Product}
     */
    public function issue(
        Product $product,
        int $qty,
        ?string $subSku,
        ?string $note
    ): array {
        $this->ensurePositiveQuantity($qty);

        return DB::transaction(function () use ($product, $qty, $subSku, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);
            $productBefore = $productForUpdate->qtyCurrent();

            $createIfMissing = $this->shouldAllowImplicitBatchCreation($subSku);
            $batch = $this->batchResolver->resolveForProduct($productForUpdate, $subSku, null, $createIfMissing);
            $batchForUpdate = ProductBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $batchBefore = $batchForUpdate->qty;

            if ($batchBefore < $qty) {
                throw ValidationException::withMessages([
                    'qty' => 'ยอดคงเหลือของล็อตนี้ไม่เพียงพอ',
                ]);
            }

            $batchForUpdate->qty = $batchBefore - $qty;
            $batchForUpdate->save();

            $productAfter = $this->refreshProductQty($productForUpdate);
            $batchAfter = $batchForUpdate->qty;

            $movement = StockMovement::create([
                'product_id' => $productForUpdate->id,
                'batch_id' => $batchForUpdate->id,
                'type' => 'out',
                'qty' => $qty,
                'note' => $note,
                'actor_id' => $actorId,
                'happened_at' => now(),
            ]);

            return [
                'movement' => $movement,
                'product_before' => $productBefore,
                'product_after' => $productAfter,
                'batch_before' => $batchBefore,
                'batch_after' => $batchAfter,
                'batch' => $batchForUpdate->fresh(),
                'product' => $productForUpdate->fresh(),
            ];
        });
    }

    /**
     * @return array{movement: StockMovement, product_before: int, product_after: int, batch_before: int, batch_after: int, batch: ProductBatch, product: Product}
     */
    public function adjust(
        Product $product,
        int $newQtyForBatch,
        string $subSku,
        ?string $note
    ): array {
        if ($newQtyForBatch < 0) {
            throw ValidationException::withMessages([
                'target_qty' => 'จำนวนต้องไม่ติดลบ',
            ]);
        }

        return DB::transaction(function () use ($product, $newQtyForBatch, $subSku, $note) {
            $actorId = $this->currentActorId();

            $productForUpdate = Product::query()->lockForUpdate()->findOrFail($product->id);
            $productBefore = $productForUpdate->qtyCurrent();

            $createIfMissing = $this->shouldAllowImplicitBatchCreation($subSku);
            $batch = $this->batchResolver->resolveForProduct($productForUpdate, $subSku, null, $createIfMissing);
            $batchForUpdate = ProductBatch::query()->whereKey($batch->id)->lockForUpdate()->firstOrFail();
            $batchBefore = $batchForUpdate->qty;

            $batchForUpdate->qty = $newQtyForBatch;
            $batchForUpdate->save();

            $productAfter = $this->refreshProductQty($productForUpdate);
            $batchAfter = $batchForUpdate->qty;
            $delta = $batchAfter - $batchBefore;

            if ($productAfter < 0) {
                throw ValidationException::withMessages([
                    'target_qty' => 'ผลรวมคงเหลือทั้งหมดต้องไม่ติดลบ',
                ]);
            }

            $noteToStore = $this->buildAdjustNote($note, $delta);

            $movement = StockMovement::create([
                'product_id' => $productForUpdate->id,
                'batch_id' => $batchForUpdate->id,
                'type' => 'adjust',
                'qty' => abs($delta),
                'note' => $noteToStore,
                'actor_id' => $actorId,
                'happened_at' => now(),
            ]);

            return [
                'movement' => $movement,
                'product_before' => $productBefore,
                'product_after' => $productAfter,
                'batch_before' => $batchBefore,
                'batch_after' => $batchAfter,
                'batch' => $batchForUpdate->fresh(),
                'product' => $productForUpdate->fresh(),
            ];
        });
    }

    private function ensurePositiveQuantity(int $qty): void
    {
        if ($qty <= 0) {
            throw ValidationException::withMessages([
                'qty' => 'จำนวนต้องมากกว่าศูนย์',
            ]);
        }
    }

    private function shouldAllowImplicitBatchCreation(?string $subSku): bool
    {
        $value = trim((string) $subSku);

        return $value === '' || $value === BatchResolver::UNSPECIFIED_TOKEN;
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

    private function refreshProductQty(Product $product): int
    {
        $total = ProductBatch::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->sum('qty');

        $product->qty = (int) $total;
        $product->save();

        return (int) $total;
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
}
