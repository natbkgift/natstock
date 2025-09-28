<?php

declare(strict_types=1);

namespace App;

use Tests\Framework\TestResponse;

/**
 * Minimal application kernel that returns deterministic responses for tests.
 */
final class Application
{
    public static function handle(string $method, string $uri, ?object $user = null): TestResponse
    {
        if ($method !== 'GET') {
            return TestResponse::make(405, 'Method Not Allowed');
        }

        return match ($uri) {
            '/login' => self::loginPage(),
            '/admin/dashboard' => self::dashboard($user),
            default => TestResponse::make(404, 'ไม่พบหน้า'),
        };
    }

    private static function loginPage(): TestResponse
    {
        $body = <<<'HTML'
        <h1>เข้าสู่ระบบหลังบ้าน</h1>
        <p>กรุณากรอกอีเมลและรหัสผ่านเพื่อเข้าใช้งาน</p>
        HTML;

        return TestResponse::make(200, $body);
    }

    private static function dashboard(?object $user): TestResponse
    {
        if ($user === null) {
            return TestResponse::make(302, 'redirect:/login');
        }

        $body = <<<'HTML'
        <h1>แดชบอร์ดภาพรวม</h1>
        <p>ยินดีต้อนรับสู่ระบบบริหารสต็อกสินค้า</p>
        HTML;

        return TestResponse::make(200, $body);
    }
}

