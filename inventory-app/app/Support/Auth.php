<?php
namespace App\Support;

use App\Models\User;

class Auth
{
    protected static ?self $instance = null;
    protected ?User $user = null;

    public static function instance(): self
    {
        if (!static::$instance) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function user(): ?User
    {
        if ($this->user) {
            return $this->user;
        }

        if (isset($_SESSION['user_id'])) {
            $this->user = User::find((int) $_SESSION['user_id']);
        }

        return $this->user;
    }

    public function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user->password)) {
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION['user_id'] = $user->id;
        $this->user = $user;
        return true;
    }

    public function logout(): void
    {
        unset($_SESSION['user_id']);
        $this->user = null;
    }
}
