<?php

namespace Illuminate\Database\Schema;

class Blueprint
{
    public string $charset = 'utf8mb4';
    public string $collation = 'utf8mb4_unicode_ci';
    public ?string $comment = null;

    public function id(): self
    {
        return $this;
    }

    public function string(string $column, int $length = 255): self
    {
        return $this;
    }

    public function text(string $column): self
    {
        return $this;
    }

    public function boolean(string $column): self
    {
        return $this;
    }

    public function timestamps(): self
    {
        return $this;
    }

    public function unique(string|array $columns, ?string $name = null): self
    {
        return $this;
    }

    public function index(string|array $columns, ?string $name = null): self
    {
        return $this;
    }

    public function decimal(string $column, int $total, int $places): self
    {
        return $this;
    }

    public function date(string $column): self
    {
        return $this;
    }

    public function unsignedInteger(string $column): self
    {
        return $this;
    }

    public function integer(string $column): self
    {
        return $this;
    }

    public function foreignId(string $column): self
    {
        return $this;
    }

    public function constrained(string $table = '', string $column = 'id'): self
    {
        return $this;
    }

    public function cascadeOnUpdate(): self
    {
        return $this;
    }

    public function restrictOnDelete(): self
    {
        return $this;
    }

    public function enum(string $column, array $allowed): self
    {
        return $this;
    }

    public function timestampTz(string $column): self
    {
        return $this;
    }

    public function nullable(): self
    {
        return $this;
    }

    public function default(mixed $value): self
    {
        return $this;
    }

    public function comment(string $comment): self
    {
        $this->comment = $comment;

        return $this;
    }

    public function rememberToken(): self
    {
        return $this;
    }
}
