<?php

namespace Illuminate\Database\Eloquent;

class Builder
{
    public function __construct(private readonly string $modelClass)
    {
    }

    public function where(mixed ...$arguments): self
    {
        return $this;
    }

    public function whereColumn(mixed ...$arguments): self
    {
        return $this;
    }

    public function whereNotNull(mixed ...$arguments): self
    {
        return $this;
    }

    public function whereBetween(mixed ...$arguments): self
    {
        return $this;
    }

    public function pluck(string $column, ?string $key = null): array
    {
        return [];
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $class = $this->modelClass;

        return new $class(array_merge($attributes, $values));
    }
}
