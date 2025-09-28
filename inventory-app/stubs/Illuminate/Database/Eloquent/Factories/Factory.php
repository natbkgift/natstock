<?php

namespace Illuminate\Database\Eloquent\Factories;

abstract class Factory
{
    /**
     * @var class-string
     */
    protected string $model;

    /** @var list<callable(): array> */
    private array $states = [];

    /**
     * Create a new model instance with optional attribute overrides.
     */
    public function create(array $attributes = []): object
    {
        $class = $this->model;
        $data = array_merge($this->definition(), ...array_map(fn ($state) => $state(), $this->states), $attributes);

        return new $class($data);
    }

    public function make(array $attributes = []): object
    {
        return $this->create($attributes);
    }

    public function count(int $count): array
    {
        $items = [];

        for ($i = 0; $i < $count; $i++) {
            $items[] = $this->create();
        }

        return $items;
    }

    public function state(callable $callback): static
    {
        $clone = clone $this;
        $clone->states[] = $callback;

        return $clone;
    }

    abstract public function definition(): array;
}
