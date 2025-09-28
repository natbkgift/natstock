<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function __construct(private readonly AuditLogger $logger)
    {
    }

    public function handle(Login $event): void
    {
        $this->logger->log(
            'auth.login',
            'ผู้ใช้เข้าสู่ระบบ',
            ['email' => $event->user->email ?? null],
            actor: $event->user
        );
    }
}
