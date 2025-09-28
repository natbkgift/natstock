<?php

namespace Illuminate\Support;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Collection implements Countable, IteratorAggregate
{
    /** @var array<int, mixed> */
    protected array $items;

    /**
     * @param  iterable<mixed>  $items
     */
    public function __construct(iterable $items = [])
    {
        $this->items = [];

        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    public static function make(iterable $items = []): self
    {
        return $items instanceof self ? $items : new self($items);
    }

    /** @return array<int, mixed> */
    public function all(): array
    {
        return $this->items;
    }

    public function map(callable $callback): self
    {
        $mapped = [];
        foreach ($this->items as $key => $item) {
            $mapped[] = $callback($item, $key);
        }

        return new self($mapped);
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        $carry = $initial;
        foreach ($this->items as $key => $item) {
            $carry = $callback($carry, $item, $key);
        }

        return $carry;
    }

    public function sum(callable $callback): float
    {
        $total = 0.0;
        foreach ($this->items as $key => $item) {
            $total += (float) $callback($item, $key);
        }

        return $total;
    }

    public function firstWhere(string $key, mixed $expected): mixed
    {
        foreach ($this->items as $item) {
            $value = null;

            if (is_array($item) && array_key_exists($key, $item)) {
                $value = $item[$key];
            } elseif (is_object($item) && isset($item->{$key})) {
                $value = $item->{$key};
            }

            if ($value == $expected) {
                return $item;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
