<?php

namespace App\Services;

use RuntimeException;
use Throwable;

class ImportProcessingException extends RuntimeException
{
    public function __construct(
        public readonly ImportResult $result,
        string $message,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
