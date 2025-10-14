<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;

class LotService
{
    public function nextFor(Product $product): string
    {
        return DB::transaction(function () use ($product) {
            $record = DB::table('product_lot_counters')
                ->where('product_id', $product->getKey())
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                DB::table('product_lot_counters')->insert([
                    'product_id' => $product->getKey(),
                    'next_no' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $current = 1;
            } else {
                $current = (int) $record->next_no;
            }

            DB::table('product_lot_counters')
                ->where('product_id', $product->getKey())
                ->update([
                    'next_no' => $current + 1,
                    'updated_at' => now(),
                ]);

            return sprintf('LOT-%02d', $current);
        }, 3);
    }
}
