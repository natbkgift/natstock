<?php

namespace App\Services;

class ImportResult
{
    public int $products_created = 0;
    public int $products_updated = 0;
    public int $batches_created = 0;
    public int $batches_updated = 0;
    public int $movements_created = 0;
    public int $rows_ok = 0;
    public int $rows_error = 0;
    public ?string $error_csv_path = null;
    /**
     * @var list<string>
     */
    public array $ignored_columns = [];

    public bool $strict_rolled_back = false;
}
