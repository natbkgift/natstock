<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Support\Collection;

abstract class Model
{
    /** @var array<string, array<int, static>> */
    private static array $storage = [];

    /** @var array<string, int> */
    private static array $increments = [];

    /** @var array<string, mixed> */
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getKey(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public function fill(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public static function query(): Builder
    {
        return new Builder(static::class);
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $storage = &self::$storage[static::class];
        $increments = &self::$increments[static::class];

        if ($storage === null) {
            $storage = [];
        }
        if ($increments === null) {
            $increments = 1;
        }

        if (!array_key_exists('id', $model->attributes)) {
            $model->attributes['id'] = $increments++;
        } else {
            $increments = max($increments, (int) $model->attributes['id'] + 1);
        }

        $storage[(int) $model->attributes['id']] = $model;

        return $model;
    }

    public function update(array $attributes): bool
    {
        $this->fill($attributes);
        $storage = &self::$storage[static::class];
        $storage[(int) $this->getKey()] = $this;

        return true;
    }

    public static function all(): Collection
    {
        $storage = self::$storage[static::class] ?? [];

        return new Collection(array_values($storage));
    }

    public static function resetStubState(): void
    {
        self::$storage[static::class] = [];
        self::$increments[static::class] = 1;
    }
}
