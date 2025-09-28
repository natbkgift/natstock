<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifyService
{
    public function send(string $message): bool
    {
        $token = config('services.line_notify.token');
        if (blank($token)) {
            Log::channel('daily')->warning('ข้ามการส่ง LINE Notify เนื่องจากยังไม่ได้ตั้งค่าโทเค็น');

            return false;
        }

        $message = mb_substr($message, 0, 990);

        try {
            $response = Http::asForm()
                ->withToken($token)
                ->timeout(10)
                ->post('https://notify-api.line.me/api/notify', [
                    'message' => $message,
                ]);

            if (! $response->successful()) {
                Log::channel('daily')->error('LINE Notify ตอบกลับด้วยสถานะที่ไม่สำเร็จ', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }
        } catch (\Throwable $exception) {
            Log::channel('daily')->error('การส่ง LINE Notify ล้มเหลว', ['error' => $exception->getMessage()]);

            return false;
        }

        Log::channel('daily')->info('ส่ง LINE Notify สำเร็จ');

        return true;
    }
}
