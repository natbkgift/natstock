<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected string $model = User::class;

    private static int $sequence = 1;

    public function definition(): array
    {
        $id = self::$sequence;
        self::$sequence++;

        return [
            'id' => $id,
            'name' => 'เจ้าหน้าที่ทดสอบ ' . $id,
            'email' => sprintf('tester%02d@example.com', $id),
            'email_verified_at' => date('Y-m-d H:i:s'),
            'password' => bcrypt('password'),
            'role' => 'viewer',
            'remember_token' => 'token' . $id,
        ];
    }
}
