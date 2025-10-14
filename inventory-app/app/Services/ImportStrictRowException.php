<?php

namespace App\Services;

use RuntimeException;

class ImportStrictRowException extends RuntimeException
{
    /**
     * @param list<string> $errors
     * @param list<string> $rawRow
     */
    public function __construct(
        public readonly int $rowNumber,
        public readonly array $errors,
        public readonly array $rawRow
    ) {
        parent::__construct($errors[0] ?? 'พบข้อผิดพลาดระหว่างนำเข้า');
    }
}
