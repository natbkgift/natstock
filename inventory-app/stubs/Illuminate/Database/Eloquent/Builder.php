<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Collection;

class Builder
{
    /** @var list<array{string, string, mixed}> */
    private array $wheres = [];

    /** @var list<array{string, bool}> */
    private array $nullChecks = [];

    /** @var list<callable> */
    private array $orderCallbacks = [];

    public function __construct(private readonly string $modelClass)
    {
    }

    public function where(string $column, mixed $operator = null, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->wheres[] = [$column, (string) $operator, $value];

        return $this;
    }

    public function orWhere(string $column, mixed $operator = null, mixed $value = null): self
    {
        // For the simplified environment, treat orWhere the same as where.
        return $this->where($column, $operator, $value);
    }

    public function whereNotNull(string $column): self
    {
        $this->nullChecks[] = [$column, true];

        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->nullChecks[] = [$column, false];

        return $this;
    }

    public function first(): ?Model
    {
        $collection = $this->get();

        return $collection->first();
    }

    public function firstWhere(string $column, mixed $value): ?Model
    {
        return $this->where($column, '=', $value)->first();
    }

    public function value(string $column): mixed
    {
        $first = $this->first();

        return $first?->{$column};
    }

    public function get(): Collection
    {
        $class = $this->modelClass;
        $collection = $class::all();
        $filtered = $collection->filter(function ($model): bool {
            foreach ($this->wheres as [$column, $operator, $value]) {
                $actual = $model?->{$column} ?? null;
                if (!$this->compare($actual, $operator, $value)) {
                    return false;
                }
            }

            foreach ($this->nullChecks as [$column, $shouldBeNotNull]) {
                $actual = $model?->{$column} ?? null;
                if ($shouldBeNotNull && $actual === null) {
                    return false;
                }
                if (!$shouldBeNotNull && $actual !== null) {
                    return false;
                }
            }

            return true;
        });

        foreach ($this->orderCallbacks as $callback) {
            $items = $filtered->all();
            usort($items, $callback);
            $filtered = new Collection($items);
        }

        return $filtered;
    }

    public function all(): Collection
    {
        return $this->get();
    }

    public function create(array $attributes): Model
    {
        $class = $this->modelClass;

        return $class::create($attributes);
    }

    public function select(mixed ...$args): self
    {
        unset($args);

        return $this;
    }

    public function selectRaw(string $expression, array $bindings = []): self
    {
        unset($expression, $bindings);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): self
    {
        $this->orderCallbacks[] = function ($a, $b) use ($column, $direction): int {
            $valueA = $a?->{$column} ?? null;
            $valueB = $b?->{$column} ?? null;

            if ($valueA === $valueB) {
                return 0;
            }

            $comparison = $valueA <=> $valueB;

            return strtolower($direction) === 'desc' ? -$comparison : $comparison;
        };

        return $this;
    }

    public function with(mixed $relations): self
    {
        unset($relations);

        return $this;
    }

    public function when(mixed $value, callable $callback): self
    {
        if ($value) {
            $callback($this, $value);
        }

        return $this;
    }

    public function paginate(int $perPage): object
    {
        $items = $this->get();

        return new class($items) implements \IteratorAggregate
        {
            public function __construct(private Collection $items)
            {
            }

            public function withQueryString(): self
            {
                return $this;
            }

            public function getIterator(): \Traversable
            {
                return $this->items->getIterator();
            }

            public function items(): Collection
            {
                return $this->items;
            }
        };
    }

    private function compare(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            '=', '==' => $actual == $expected,
            '===', 'strict' => $actual === $expected,
            '!=', '<>' => $actual != $expected,
            '>' => $actual > $expected,
            '>=' => $actual >= $expected,
            '<' => $actual < $expected,
            '<=' => $actual <= $expected,
            'like' => $this->matchesLike($actual, (string) $expected),
            default => $actual == $expected,
        };
    }

    private function matchesLike(mixed $actual, string $pattern): bool
    {
        if (!is_string($actual)) {
            return false;
        }

        $regex = '/^' . str_replace('%', '.*', preg_quote($pattern, '/')) . '$/i';

        return (bool) preg_match($regex, $actual);
    }
}
