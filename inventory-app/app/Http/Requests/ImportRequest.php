<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:10240'],
            'duplicate_mode' => ['required', 'in:UPSERT,SKIP'],
            'auto_create_category' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'กรุณาเลือกไฟล์สำหรับนำเข้า',
            'file.file' => 'ไฟล์ที่อัปโหลดไม่ถูกต้อง',
            'file.mimes' => 'รองรับเฉพาะไฟล์ .csv หรือ .xlsx เท่านั้น',
            'file.max' => 'ไฟล์มีขนาดเกิน 10MB',
            'duplicate_mode.required' => 'กรุณาเลือกโหมดเมื่อตรวจพบ SKU ซ้ำ',
            'duplicate_mode.in' => 'โหมดซ้ำต้องเป็น UPSERT หรือ SKIP เท่านั้น',
            'auto_create_category.boolean' => 'ค่าเปิด/ปิดการสร้างหมวดหมู่ต้องเป็นค่าที่ถูกต้อง',
        ];
    }

    public function uploadedFile(): mixed
    {
        return $this->file('file');
    }

    public function duplicateMode(): string
    {
        $mode = strtoupper((string) $this->input('duplicate_mode', 'UPSERT'));

        return in_array($mode, ['UPSERT', 'SKIP'], true) ? $mode : 'UPSERT';
    }

    public function autoCreateCategory(): bool
    {
        return $this->boolean('auto_create_category');
    }
}
