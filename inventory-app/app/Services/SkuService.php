<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SkuService
{
    public function next(): string
    {
        return DB::transaction(function () {
            $sequence = DB::table('sequences')
                ->where('key', 'SKU')
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                throw new RuntimeException('SKU sequence is not initialized.');
            }

            $current = (int) $sequence->next_val;

            DB::table('sequences')
                ->where('key', 'SKU')
                ->update([
                    'next_val' => $current + 1,
                    'updated_at' => now(),
                ]);

            return sprintf('SKU-%04d', $current);
        }, 3);
    }
}
