<?php

namespace App\Services;

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

        $ch = curl_init('https://notify-api.line.me/api/notify');
        if ($ch === false) {
            Log::channel('daily')->error('ไม่สามารถเริ่มการเชื่อมต่อไปยัง LINE Notify ได้');

            return false;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$token,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS => http_build_query(['message' => $message]),
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $errorNo = curl_errno($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $errorNo !== 0) {
            Log::channel('daily')->error('การส่ง LINE Notify ล้มเหลว', ['error' => $errorNo]);

            return false;
        }

        if ($httpStatus !== 200) {
            Log::channel('daily')->error('LINE Notify ตอบกลับด้วยสถานะที่ไม่สำเร็จ', ['status' => $httpStatus, 'response' => $response]);

            return false;
        }

        Log::channel('daily')->info('ส่ง LINE Notify สำเร็จ');

        return true;
    }
}
