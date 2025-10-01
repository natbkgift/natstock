<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class InventoryDailySummaryMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $summary
     */
    public function __construct(private readonly array $summary, private readonly bool $isTest = false)
    {
    }

    public static function forTest(array $summary): self
    {
        return new self($summary, true);
    }

    public function build(): self
    {
        Carbon::setLocale('th');
        $date = Carbon::now('Asia/Bangkok')->translatedFormat('j F Y');

        $subjectPrefix = $this->isTest ? '[ทดสอบ] ' : '';

        return $this
            ->subject($subjectPrefix.'[แจ้งเตือนคลัง] สรุปใกล้หมดอายุ/สต็อกต่ำ - '.$date)
            ->view('emails.inventory.daily_summary')
            ->with([
                'summary' => $this->summary,
                'thaiDate' => $date,
                'appUrl' => config('app.url'),
                'isTest' => $this->isTest,
            ]);
    }
}
