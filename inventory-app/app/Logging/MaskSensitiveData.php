<?php

namespace App\Logging;

use Monolog\Logger;

class MaskSensitiveData
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(function (array $record) {
            $record['context'] = $this->maskContext($record['context'] ?? []);
            $record['extra'] = $this->maskContext($record['extra'] ?? []);

            return $record;
        });
    }

    private function maskContext(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $context[$key] = $this->maskContext($value);
                continue;
            }

            if (is_string($value) && $this->isSensitiveKey($key)) {
                $context[$key] = $this->maskString($value);
            }
        }

        return $context;
    }

    private function maskString(string $value): string
    {
        return '***MASKED***';
    }

    private function isSensitiveKey(string $key): bool
    {
        return str_contains(strtolower($key), 'token')
            || str_contains(strtolower($key), 'password')
            || str_contains(strtolower($key), 'secret')
            || str_contains(strtolower($key), 'key');
    }
}
