<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;

class MaskSensitiveData
{
    public function __invoke(IlluminateLogger $logger): void
    {
        // Push the processor into the underlying Monolog logger when available
        $underlying = $logger->getLogger();
        if ($underlying instanceof MonologLogger) {
            $underlying->pushProcessor(function (array|LogRecord $record) {
                if ($record instanceof LogRecord) {
                    $maskedContext = $this->maskContext($record->context ?? []);
                    $maskedExtra = $this->maskContext($record->extra ?? []);

                    return $record->with(context: $maskedContext, extra: $maskedExtra);
                }

                $record['context'] = $this->maskContext($record['context'] ?? []);
                $record['extra'] = $this->maskContext($record['extra'] ?? []);

                return $record;
            });
            return;
        }

        // Fallback: if pushProcessor isn't available, attempt context masking via context array
        $logger->withContext($this->maskContext([]));
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
