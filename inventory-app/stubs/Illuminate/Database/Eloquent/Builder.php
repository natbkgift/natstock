<?php

namespace Illuminate\Database\Eloquent;

class Builder
{
    /** @var array<string, mixed> */
    private array $conditions = [];

    public function __construct(private readonly string $modelClass)
    {
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $this->conditions[$column] = $operator;
        } else {
            $this->conditions[$column] = $value;
        }

        return $this;
    }

    public function first(): ?Model
    {
        foreach ($this->records() as $record) {
            if ($this->matches($record)) {
                $class = $this->modelClass;

                return new $class($record);
            }
        }

        return null;
    }

    public function firstWhere(string $column, mixed $value, string $operator = '='): ?Model
    {
        return $this->where($column, $value)->first();
    }

    public function firstOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->withAttributes($attributes)->first();
        if ($model !== null) {
            return $model;
        }

        $class = $this->modelClass;

        return $class::create(array_merge($attributes, $values));
    }

    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        $model = $this->withAttributes($attributes)->first();
        if ($model !== null) {
            $model->update($values);

            return $model;
        }

        $class = $this->modelClass;

        return $class::create(array_merge($attributes, $values));
    }

    public function create(array $attributes): Model
    {
        $class = $this->modelClass;

        return $class::create($attributes);
    }

    private function records(): array
    {
        return call_user_func([$this->modelClass, 'getStoredRecords']);
    }

    private function matches(array $record): bool
    {
        foreach ($this->conditions as $column => $value) {
            if (!array_key_exists($column, $record)) {
                return false;
            }

            if ($record[$column] != $value) {
                return false;
            }
        }

        return true;
    }

    private function withAttributes(array $attributes): self
    {
        $builder = new self($this->modelClass);
        foreach ($attributes as $key => $value) {
            $builder->where($key, $value);
        }

        return $builder;
    }
}
