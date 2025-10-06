<?php

namespace App\Support;

class PriceGuard
{
    /**
     * Strip pricing fields from the payload when the pricing feature is disabled.
     */
    public static function strip(array &$payload): void
    {
        if (config('inventory.enable_price')) {
            return;
        }

        unset($payload['cost_price'], $payload['sale_price']);
    }
}
