<?php
namespace App\Http\Controllers;

class Controller
{
    protected function validate(array $rules, array $data): array
    {
        $errors = [];
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            foreach (explode('|', $rule) as $condition) {
                if ($condition === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = 'จำเป็นต้องกรอก';
                } elseif ($condition === 'numeric' && !is_numeric($value)) {
                    $errors[$field] = 'ต้องเป็นตัวเลข';
                } elseif ($condition === 'integer' && filter_var($value, FILTER_VALIDATE_INT) === false) {
                    $errors[$field] = 'ต้องเป็นจำนวนเต็ม';
                } elseif (str_starts_with($condition, 'min:')) {
                    $min = (int) substr($condition, 4);
                    if ((int) $value < $min) {
                        $errors[$field] = 'ต้องไม่น้อยกว่า '.$min;
                    }
                } elseif ($condition === 'date' && $value && !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $value)) {
                    $errors[$field] = 'รูปแบบวันที่ไม่ถูกต้อง';
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['_errors'] = $errors;
            $_SESSION['_old'] = $data;
            return $errors;
        }

        $_SESSION['_errors'] = [];
        $_SESSION['_old'] = [];
        return [];
    }
}
