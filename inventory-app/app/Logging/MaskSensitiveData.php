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
                $contextData = $record instanceof LogRecord
                    ? ($record->context ?? [])
                    : ($record['context'] ?? []);
                $extraData = $record instanceof LogRecord
                    ? ($record->extra ?? [])
                    : ($record['extra'] ?? []);

                $context = is_array($contextData) ? $contextData : [];
                $extra = is_array($extraData) ? $extraData : [];

                $maskedContext = $this->maskContext($context);
                $maskedExtra = $this->maskContext($extra);

                if ($record instanceof LogRecord) {
                    return $record->with(context: $maskedContext, extra: $maskedExtra);
                }

                $record['context'] = $maskedContext;
                $record['extra'] = $maskedExtra;

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
