<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $sku = $this->input('sku');
        if ($sku !== null) {
            $sku = trim((string) $sku);
            $this->merge(['sku' => $sku === '' ? null : $sku]);
        }

        $newCategory = $this->input('new_category_name');
        if ($newCategory === null) {
            $newCategory = $this->input('new_category');
        }

        if ($newCategory !== null) {
            $newCategory = trim((string) $newCategory);
            $this->merge(['new_category_name' => $newCategory === '' ? null : $newCategory]);
        }

        if ($this->has('category_id') && $this->input('category_id') === '') {
            $this->merge(['category_id' => null]);
        }

        if (filled($this->input('new_category_name'))) {
            $this->merge(['category_id' => null]);
        }
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        if ($productId instanceof Product) {
            $productId = $productId->getKey();
        }

        if (is_string($productId) && ctype_digit($productId)) {
            $productId = (int) $productId;
        }

        $uniqueSkuRule = Rule::unique('products', 'sku');

        if ($productId) {
            $uniqueSkuRule->ignore($productId);
        }

        $rules = [
            'sku' => [
                'nullable',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._-]+$/',
                $uniqueSkuRule,
            ],
            'name' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'new_category_name' => ['nullable', 'string', 'max:120'],
            'expire_date' => ['nullable', 'date_format:Y-m-d'],
            'reorder_point' => ['integer', 'min:0'],
            'qty' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'note' => ['nullable', 'string'],
        ];

        if (config('inventory.enable_price')) {
            $rules['cost_price'] = ['nullable', 'numeric', 'min:0'];
            $rules['sale_price'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'sku.required' => 'กรุณากรอกรหัสสินค้า',
            'sku.string' => 'รหัสสินค้าต้องเป็นตัวอักษรหรือตัวเลข',
            'sku.max' => 'รหัสสินค้าต้องไม่เกิน 64 ตัวอักษร',
            'sku.regex' => 'รหัสสินค้าอนุญาตเฉพาะตัวอักษร ตัวเลข จุด ขีดกลาง และขีดล่าง',
            'sku.unique' => 'รหัสสินค้านี้ถูกใช้แล้ว',
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'name.string' => 'ชื่อสินค้าต้องเป็นข้อความ',
            'name.max' => 'ชื่อสินค้าต้องไม่เกิน 150 ตัวอักษร',
            'category_id.exists' => 'หมวดหมู่สินค้าที่เลือกไม่ถูกต้อง',
            'new_category_name.string' => 'ชื่อหมวดหมู่ใหม่ต้องเป็นข้อความ',
            'new_category_name.max' => 'ชื่อหมวดหมู่ใหม่ต้องไม่เกิน 120 ตัวอักษร',
            'cost_price.numeric' => 'ราคาทุนต้องเป็นตัวเลข',
            'cost_price.min' => 'ราคาทุนต้องมากกว่าหรือเท่ากับ 0',
            'sale_price.numeric' => 'ราคาขายต้องเป็นตัวเลข',
            'sale_price.min' => 'ราคาขายต้องมากกว่าหรือเท่ากับ 0',
            'expire_date.date_format' => 'วันหมดอายุต้องอยู่ในรูปแบบ YYYY-MM-DD',
            'reorder_point.integer' => 'จุดสั่งซื้อซ้ำต้องเป็นจำนวนเต็ม',
            'reorder_point.min' => 'จุดสั่งซื้อซ้ำต้องมากกว่าหรือเท่ากับ 0',
            'qty.integer' => 'ปริมาณคงเหลือต้องเป็นจำนวนเต็ม',
            'qty.min' => 'ปริมาณคงเหลือต้องมากกว่าหรือเท่ากับ 0',
            'is_active.boolean' => 'สถานะการใช้งานต้องเป็นค่าใช่/ไม่ใช่',
            'note.string' => 'หมายเหตุต้องเป็นข้อความ',
        ];
    }
}
