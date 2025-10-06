<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackfillProductBatches extends Command
{
    protected $signature = 'backfill:product-batches';

    protected $description = 'ย้ายยอดคงเหลือจาก products.qty ไปยัง lot UNSPECIFIED เพื่อเตรียมหลายล็อต';

    public function handle(): int
    {
        $this->info('เริ่ม backfill ยอดคงเหลือเข้าสู่ product_batches...');

        $createdBatches = 0;
        $skippedZeroQty = 0;
        $skippedExisting = 0;

        try {
            Product::query()
                ->with('batches')
                ->orderBy('id')
                ->chunkById(100, function ($products) use (&$createdBatches, &$skippedZeroQty, &$skippedExisting) {
                    DB::transaction(function () use ($products, &$createdBatches, &$skippedZeroQty, &$skippedExisting) {
                        foreach ($products as $product) {
                            $qty = (int) $product->qty;

                            if ($qty === 0) {
                                ++$skippedZeroQty;

                                continue;
                            }

                            if ($qty < 0) {
                                throw new RuntimeException(
                                    "Product ID {$product->id} has negative quantity ({$qty}), which cannot be backfilled."
                                );
                            }

                            $unspecifiedSubSku = $product->sku.'-UNSPECIFIED';
                            $existingBatch = $product->batches->firstWhere('sub_sku', $unspecifiedSubSku);

                            if ($existingBatch !== null) {
                                ++$skippedExisting;

                                continue;
                            }

                            $batch = ProductBatch::firstOrCreate(
                                [
                                    'product_id' => $product->id,
                                    'sub_sku' => $unspecifiedSubSku,
                                ],
                                [
                                    'expire_date' => null,
                                    'qty' => $qty,
                                    'note' => 'สร้างอัตโนมัติเพื่อคงยอดเดิมก่อนรองรับหลายล็อต',
                                    'is_active' => true,
                                ]
                            );

                            if ($batch->wasRecentlyCreated) {
                                ++$createdBatches;
                            }
                        }
                    });
                });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            $this->error('การ backfill ถูกยกเลิก กรุณาแก้ไขข้อมูลก่อนรันคำสั่งอีกครั้ง');

            return Command::FAILURE;
        }

        $this->info("สร้าง batch UNSPECIFIED ใหม่จำนวน {$createdBatches} รายการ");

        if ($skippedExisting > 0) {
            $this->info("ข้ามสินค้า {$skippedExisting} รายการที่มี batch UNSPECIFIED อยู่แล้ว");
        }

        if ($skippedZeroQty > 0) {
            $this->info("ข้ามสินค้า {$skippedZeroQty} รายการที่มียอดคงเหลือเป็น 0");
        }

        $this->info('สำเร็จแล้ว สามารถเริ่มใช้งานโครงสร้าง lot ใหม่ได้');

        return Command::SUCCESS;
    }
}
