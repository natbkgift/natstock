<?php

namespace Illuminate\Validation;

class Rule
{
    public function __construct(private readonly string $rule)
    {
    }

    public static function unique(string $table, string $column): self
    {
        return new self("unique:{$table},{$column}");
    }

    public function ignore(int|string $id): self
    {
        return new self($this->rule . ',' . $id);
    }

    public function __toString(): string
    {
        return $this->rule;
    }
}
