<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category');

        if ($categoryId instanceof Category) {
            $categoryId = $categoryId->getKey();
        }

        if (is_string($categoryId) && ctype_digit($categoryId)) {
            $categoryId = (int) $categoryId;
        }

        $uniqueNameRule = Rule::unique('categories', 'name');

        if ($categoryId) {
            $uniqueNameRule->ignore($categoryId);
        }

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                $uniqueNameRule,
            ],
            'note' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อหมวดหมู่',
            'name.string' => 'ชื่อหมวดหมู่ต้องเป็นตัวอักษร',
            'name.max' => 'ชื่อหมวดหมู่ต้องไม่เกิน 100 ตัวอักษร',
            'name.unique' => 'ชื่อหมวดหมู่ซ้ำกับรายการที่มีอยู่แล้ว',
            'note.string' => 'หมายเหตุต้องเป็นข้อความ',
            'is_active.boolean' => 'สถานะการใช้งานต้องเป็นค่าใช่/ไม่ใช่',
        ];
    }
}
