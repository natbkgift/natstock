<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function __construct(private readonly AuditLogger $logger)
    {
    }

    public function handle(Logout $event): void
    {
        $this->logger->log(
            'auth.logout',
            'ผู้ใช้ออกจากระบบ',
            ['email' => $event->user?->email],
            actor: $event->user
        );
    }
}
