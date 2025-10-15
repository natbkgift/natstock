<?php

namespace App\Support;

use App\Models\StockMovement;
use Illuminate\Support\Str;

class ActivityPresenter
{
    public function presentMovement(StockMovement $movement): string
    {
        $product = $movement->product;
        $sku = $product?->sku ?: '-';
        $name = $product?->name ?: 'ไม่ระบุชื่อสินค้า';
        $lotValue = $movement->batch?->lot_no ?: 'ไม่ระบุ';
        $lotText = Str::startsWith(Str::upper($lotValue), 'LOT') ? $lotValue : 'LOT ' . $lotValue;
        $actor = $movement->actor?->name ?: 'ระบบ';
        $happenedAt = $movement->happened_at?->clone()->locale('th')->translatedFormat('d M Y H:i')
            ?? 'ไม่ระบุเวลา';

        return match ($movement->type) {
            'receive' => $this->formatReceive($sku, $name, $lotText, $movement->qty, $happenedAt, $actor),
            'issue' => $this->formatIssue($sku, $name, $lotText, $movement->qty, $happenedAt, $actor),
            'adjust' => $this->formatAdjust($movement, $sku, $name, $lotText, $happenedAt, $actor),
            default => $this->formatFallback($sku, $name, $lotText, $movement->qty, $happenedAt, $actor),
        };
    }

    private function formatReceive(string $sku, string $name, string $lot, int $qty, string $happenedAt, string $actor): string
    {
        return sprintf(
            'รับเข้า %s - %s (%s) จำนวน +%s เมื่อ %s โดย %s',
            $sku,
            $name,
            $lot,
            number_format($qty),
            $happenedAt,
            $actor
        );
    }

    private function formatIssue(string $sku, string $name, string $lot, int $qty, string $happenedAt, string $actor): string
    {
        return sprintf(
            'เบิกออก %s - %s (%s) จำนวน -%s เมื่อ %s โดย %s',
            $sku,
            $name,
            $lot,
            number_format($qty),
            $happenedAt,
            $actor
        );
    }

    private function formatAdjust(StockMovement $movement, string $sku, string $name, string $lot, string $happenedAt, string $actor): string
    {
        $batchQty = (int) ($movement->batch?->qty ?? 0);
        $delta = $this->resolveAdjustDelta($movement);
        $before = $batchQty - $delta;
        $after = $batchQty;

        return sprintf(
            'ปรับยอด %s - %s (%s) จาก %s เป็น %s เมื่อ %s โดย %s',
            $sku,
            $name,
            $lot,
            number_format($before),
            number_format($after),
            $happenedAt,
            $actor
        );
    }

    private function formatFallback(string $sku, string $name, string $lot, int $qty, string $happenedAt, string $actor): string
    {
        $symbol = $qty >= 0 ? '+' : '-';

        return sprintf(
            'เคลื่อนไหว %s - %s (%s) จำนวน %s%s เมื่อ %s โดย %s',
            $sku,
            $name,
            $lot,
            $symbol,
            number_format(abs($qty)),
            $happenedAt,
            $actor
        );
    }

    private function resolveAdjustDelta(StockMovement $movement): int
    {
        $delta = (int) ($movement->getAttribute('delta') ?? 0);

        if ($delta !== 0) {
            return $delta;
        }

        $note = (string) $movement->note;

        if ($note !== '' && preg_match('/Δ\s*([+-]?)(\d+)/u', $note, $matches) === 1) {
            $value = (int) $matches[2];
            $sign = $matches[1] === '-' ? -1 : 1;

            return $sign * $value;
        }

        if ($movement->qty > 0) {
            return $movement->qty;
        }

        return 0;
    }
}

