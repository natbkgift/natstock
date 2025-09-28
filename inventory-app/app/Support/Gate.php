<?php
namespace App\Support;

use App\Models\User;

class Gate
{
    public static function authorize(string $ability): bool
    {
        $user = auth()->user();
        if (!$user) {
            return false;
        }

        $map = [
            'view-dashboard' => ['admin', 'staff', 'viewer'],
            'manage-products' => ['admin', 'staff'],
            'view-products' => ['admin', 'staff', 'viewer'],
            'delete-products' => ['admin'],
            'manage-categories' => ['admin'],
            'view-categories' => ['admin', 'staff', 'viewer'],
            'manage-stock' => ['admin', 'staff'],
            'view-stock' => ['admin', 'staff', 'viewer'],
            'manage-import' => ['admin'],
            'view-import' => ['admin', 'staff'],
            'view-reports' => ['admin', 'staff', 'viewer'],
        ];

        $roles = $map[$ability] ?? [];
        return in_array($user->role, $roles, true);
    }

    public static function require(string $ability): void
    {
        if (!static::authorize($ability)) {
            http_response_code(403);
            echo 'ไม่มีสิทธิ์เข้าถึงหน้านี้';
            exit;
        }
    }
}
