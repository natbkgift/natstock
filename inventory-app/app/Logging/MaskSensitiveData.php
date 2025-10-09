<?php

namespace App\Logging;

<<<<<<< HEAD
use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Logger as MonologLogger;
use Monolog\LogRecord;
=======
use Monolog\Logger;
>>>>>>> adbbefc (fix: update MaskSensitiveData tap and upload script for hotfix deployment)

class MaskSensitiveData
{
    public function __invoke(Logger $logger): void
    {
<<<<<<< HEAD
        // Push the processor into the underlying Monolog logger when available
        $underlying = $logger->getLogger();
        if ($underlying instanceof MonologLogger) {
            $underlying->pushProcessor(function (array|LogRecord $record) {
                $context = $this->maskContext($this->extractRecordData($record, 'context'));
                $extra = $this->maskContext($this->extractRecordData($record, 'extra'));

                return $this->applyMaskedData($record, $context, $extra);
            });
            return;
        }

        // Fallback: if pushProcessor isn't available, attempt context masking via context array
        $logger->withContext($this->maskContext([]));
=======
        $logger->pushProcessor(function (array $record) {
            $record['context'] = $this->maskContext($record['context'] ?? []);
            $record['extra'] = $this->maskContext($record['extra'] ?? []);

            return $record;
        });
>>>>>>> adbbefc (fix: update MaskSensitiveData tap and upload script for hotfix deployment)
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

    /**
     * @param array|LogRecord $record
     */
    private function extractRecordData(array|LogRecord $record, string $key): array
    {
        $value = $record instanceof LogRecord
            ? ($record->{$key} ?? [])
            : ($record[$key] ?? []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array|LogRecord $record
     */
    private function applyMaskedData(array|LogRecord $record, array $context, array $extra): array|LogRecord
    {
        if ($record instanceof LogRecord) {
            return $record->with(context: $context, extra: $extra);
        }

        $record['context'] = $context;
        $record['extra'] = $extra;

        return $record;
    }
}
