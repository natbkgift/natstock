<?php

namespace Illuminate\Support;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;

class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @param  array<int|string, mixed>  $items
     */
    public function __construct(private array $items = [])
    {
    }

    /**
     * @param  array<int|string, mixed>  $items
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    public function all(): array
    {
        return $this->items;
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function push(mixed $item): self
    {
        $this->items[] = $item;

        return $this;
    }

    public function sum(callable|string|null $callback = null): float|int
    {
        if ($callback === null) {
            return array_sum($this->items);
        }

        if (is_string($callback)) {
            return array_reduce($this->items, function ($carry, $item) use ($callback) {
                return $carry + (is_array($item) ? ($item[$callback] ?? 0) : ($item->{$callback} ?? 0));
            }, 0);
        }

        return array_reduce($this->items, fn ($carry, $item) => $carry + $callback($item), 0);
    }

    public function first(callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $this->items[0] ?? $default;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return $default;
    }

    public function firstWhere(string $key, mixed $value, mixed $default = null): mixed
    {
        foreach ($this->items as $item) {
            $actual = is_array($item) ? ($item[$key] ?? null) : ($item->{$key} ?? null);
            if ($actual === $value) {
                return $item;
            }
        }

        return $default;
    }

    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items));
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }
}
