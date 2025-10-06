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
            'expiring_days' => ['required', 'integer', 'min:1', 'max:365'],
            'notify_low_stock' => ['sometimes', 'boolean'],
            'low_stock_enabled' => ['sometimes', 'boolean'],
            'expiring_enabled' => ['sometimes', 'boolean'],
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
            'expiring_days.required' => 'กรุณาระบุจำนวนวันล่วงหน้าสำหรับการแจ้งเตือนล็อตใกล้หมดอายุ',
            'expiring_days.integer' => 'จำนวนวันล่วงหน้าต้องเป็นตัวเลข',
            'expiring_days.min' => 'จำนวนวันล่วงหน้าต้องมากกว่า 0',
            'expiring_days.max' => 'จำนวนวันล่วงหน้าไม่ควรเกิน 365 วัน',
            'notify_low_stock.boolean' => 'รูปแบบข้อมูลการแจ้งเตือนสต็อกต่ำไม่ถูกต้อง',
            'low_stock_enabled.boolean' => 'รูปแบบข้อมูลการเปิดใช้งานสต็อกต่ำไม่ถูกต้อง',
            'expiring_enabled.boolean' => 'รูปแบบข้อมูลการเปิดใช้งานแจ้งเตือนใกล้หมดอายุไม่ถูกต้อง',
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
