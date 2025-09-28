<?php

namespace App\Http\Requests;

use App\Rules\CommaSeparatedEmails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SystemSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'alert_expiring_days' => ['required', 'string', 'regex:/^\s*\d+(?:\s*,\s*\d+)*\s*$/u'],
            'notify_low_stock' => ['sometimes', 'boolean'],
            'notify_channels' => ['required', 'array', 'min:1'],
            'notify_channels.*' => [Rule::in(array_keys(config('inventory.notify_channel_options', [])))],
            'notify_emails' => ['nullable', 'string', new CommaSeparatedEmails()],
            'daily_scan_time' => ['required', 'date_format:H:i'],
        ];
    }

    public function messages(): array
    {
        return [
            'alert_expiring_days.required' => 'กรุณาระบุช่วงวันสำหรับแจ้งเตือนสินค้าใกล้หมดอายุ',
            'alert_expiring_days.regex' => 'รูปแบบช่วงวันไม่ถูกต้อง (เช่น 30,60,90)',
            'notify_low_stock.boolean' => 'รูปแบบข้อมูลการแจ้งเตือนสต็อกต่ำไม่ถูกต้อง',
            'notify_channels.required' => 'กรุณาเลือกช่องทางการแจ้งเตือนอย่างน้อย 1 ช่องทาง',
            'notify_channels.array' => 'รูปแบบข้อมูลช่องทางการแจ้งเตือนไม่ถูกต้อง',
            'notify_channels.min' => 'กรุณาเลือกช่องทางการแจ้งเตือนอย่างน้อย 1 ช่องทาง',
            'notify_channels.*.in' => 'พบช่องทางการแจ้งเตือนที่ไม่รองรับ',
            'notify_emails.string' => 'กรุณาระบุอีเมลในรูปแบบข้อความ',
            'daily_scan_time.required' => 'กรุณาระบุเวลาในการสแกนประจำวัน',
            'daily_scan_time.date_format' => 'กรุณาระบุเวลาเป็นรูปแบบ HH:MM (เช่น 08:00)',
        ];
    }
}
