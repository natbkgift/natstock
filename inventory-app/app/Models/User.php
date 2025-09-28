<?php
namespace App\Models;

use PDO;

class User extends Model
{
    protected static string $table = 'users';

    public int $id;
    public string $name;
    public string $email;
    public string $password;
    public string $role;

    public static function findByEmail(string $email): ?self
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data ? static::fromArray($data) : null;
    }
}
