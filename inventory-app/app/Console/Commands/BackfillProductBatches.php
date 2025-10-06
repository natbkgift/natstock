<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillProductBatches extends Command
{
    protected $signature = 'backfill:product-batches';

    protected $description = 'ย้ายยอดคงเหลือจาก products.qty ไปยัง lot UNSPECIFIED เพื่อเตรียมหลายล็อต';

    public function handle(): int
    {
        $this->info('เริ่ม backfill ยอดคงเหลือเข้าสู่ product_batches...');

        $createdBatches = 0;
        $touchedProducts = 0;

        Product::query()->orderBy('id')->chunkById(100, function ($products) use (&$createdBatches, &$touchedProducts) {
            foreach ($products as $product) {
                DB::transaction(function () use ($product, &$createdBatches, &$touchedProducts) {
                    $qty = (int) $product->qty;
                    $unspecifiedSubSku = $product->sku.'-UNSPECIFIED';

                    if ($qty > 0) {
                        $batch = ProductBatch::updateOrCreate(
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

                    if ($product->qty !== 0) {
                        // ปรับยอดหลักกลับไปเป็น 0 เพื่อให้ระบบใช้ค่าจาก batch แทน
                        $product->qty = 0;
                        $product->save();
                        ++$touchedProducts;
                    }
                });
            }
        });

        $this->info("สร้าง batch UNSPECIFIED ใหม่จำนวน {$createdBatches} รายการ");
        $this->info("อัปเดต products.qty เป็น 0 จำนวน {$touchedProducts} รายการ");
        $this->info('สำเร็จแล้ว สามารถเริ่มใช้งานโครงสร้าง lot ใหม่ได้');

        return Command::SUCCESS;
    }
}
