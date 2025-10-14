<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Services\SkuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackfillProductBatches extends Command
{
    protected $signature = 'backfill:product-batches';

    protected $description = 'โยกยอดคงเหลือจาก products.qty ไปยัง product_batches (LOT-01) และตั้งค่ารหัสสินค้า/ล็อตอัตโนมัติ';

    public function handle(): int
    {
        $this->warn('ควรรันคำสั่งนี้นอกเวลาใช้งานจริงเพื่อป้องกันข้อมูลคลาดเคลื่อน');
        $this->info('เริ่ม backfill โครงสร้างล็อตและเลขรัน...');

        $skuService = app(SkuService::class);
        $createdLots = 0;
        $assignedSku = 0;
        $skippedExistingLot = 0;

        try {
            Product::query()
                ->with('batches')
                ->orderBy('id')
                ->chunkById(100, function ($products) use ($skuService, &$createdLots, &$assignedSku, &$skippedExistingLot) {
                    foreach ($products as $product) {
                        DB::transaction(function () use ($product, $skuService, &$createdLots, &$assignedSku, &$skippedExistingLot) {
                            if (blank($product->sku)) {
                                $product->sku = $skuService->next();
                                $product->save();
                                ++$assignedSku;
                            }

                            $legacyQty = (int) $product->qty;

                            if ($legacyQty < 0) {
                                throw new RuntimeException(
                                    "Product ID {$product->id} has negative quantity ({$legacyQty}), which cannot be backfilled."
                                );
                            }

                            $initialLot = $product->batches
                                ->firstWhere('lot_no', 'LOT-01');

                            if ($initialLot === null && $legacyQty > 0) {
                                $lotNo = 'LOT-01';

                                ProductBatch::create([
                                    'product_id' => $product->id,
                                    'lot_no' => $lotNo,
                                    'qty' => $legacyQty,
                                    'received_at' => now(),
                                    'is_active' => true,
                                ]);

                                $this->saveCounter($product->id, 2);

                                $product->qty = 0;
                                $product->save();

                                ++$createdLots;
                            } elseif ($initialLot !== null) {
                                $next = $this->determineNextLotNo($product->batches->pluck('lot_no')->all());

                                $this->saveCounter($product->id, $next);

                                ++$skippedExistingLot;
                            } else {
                                $this->saveCounter($product->id, 1);
                            }

                        });
                    }
                });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            $this->error('การ backfill ถูกยกเลิก กรุณาแก้ไขข้อมูลก่อนรันคำสั่งอีกครั้ง');

            return Command::FAILURE;
        }

        $this->info("กำหนด SKU ใหม่จำนวน {$assignedSku} รายการ");
        $this->info("สร้างล็อต LOT-01 ใหม่จำนวน {$createdLots} รายการ");

        if ($skippedExistingLot > 0) {
            $this->info("พบสินค้าที่มีล็อต LOT-01 อยู่แล้วจำนวน {$skippedExistingLot} รายการ");
        }

        $this->info('สำเร็จแล้ว สามารถตรวจสอบยอดคงเหลือผ่าน product_batches ได้');

        return Command::SUCCESS;
    }

    private function determineNextLotNo(array $lotNos): int
    {
        $max = 1;

        foreach ($lotNos as $lotNo) {
            if (preg_match('/LOT-(\d{2})/', $lotNo, $matches)) {
                $value = (int) $matches[1];
                $max = max($max, $value + 1);
            }
        }

        return max(2, $max);
    }

    private function saveCounter(int $productId, int $nextNo): void
    {
        $row = DB::table('product_lot_counters')
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        $timestamp = now();

        if ($row === null) {
            DB::table('product_lot_counters')->insert([
                'product_id' => $productId,
                'next_no' => $nextNo,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

            return;
        }

        DB::table('product_lot_counters')
            ->where('product_id', $productId)
            ->update([
                'next_no' => $nextNo,
                'updated_at' => $timestamp,
            ]);
    }
}
