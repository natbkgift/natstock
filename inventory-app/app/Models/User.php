<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
    private static int $autoIncrement = 1;

    public int $id;
    public string $name;
    public string $email;
    public string $role;

    public function __construct(array $attributes)
    {
        $this->id = $attributes['id'] ?? self::$autoIncrement++;
        $this->name = $attributes['name'] ?? 'ผู้ใช้งาน';
        $this->email = $attributes['email'] ?? 'user@example.com';
        $this->role = $attributes['role'] ?? 'admin';
    }

    public static function factory(): UserFactory
    {
        return new UserFactory();
    }
}

final class UserFactory
{
    public function create(array $attributes = []): User
    {
        return new User($attributes);
    }
}

