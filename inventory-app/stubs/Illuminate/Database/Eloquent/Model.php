<?php

namespace Illuminate\Database\Eloquent;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Model
{
    /** @var array<string, mixed> */
    protected array $attributes = [];

    /** @var array<string, string> */
    protected array $casts = [];

    /** @var list<string> */
    protected array $fillable = [];

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        if (isset($this->casts[$key])) {
            $value = $this->castAttribute($key, $value, $this->casts[$key]);
        }

        if ($key === 'id' || $this->fillable === [] || in_array($key, $this->fillable, true)) {
            $this->attributes[$key] = $value;
        }
    }

    protected function castAttribute(string $key, mixed $value, string $cast): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($cast) {
            'integer' => (int) $value,
            'boolean' => (bool) $value,
            'decimal:2' => (float) number_format((float) $value, 2, '.', ''),
            'date', 'datetime' => $value instanceof Carbon ? $value : Carbon::parse((string) $value),
            default => $value,
        };
    }

    public function getAttribute(string $key): mixed
    {
        $accessor = 'get' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key))) . 'Attribute';

        if (method_exists($this, $accessor)) {
            return $this->{$accessor}();
        }

        return $this->attributes[$key] ?? null;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function getKey(): mixed
    {
        return $this->attributes['id'] ?? null;
    }

    public static function query(): Builder
    {
        return new Builder(static::class);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        return new BelongsTo($this, $related, $foreignKey, $ownerKey);
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        return new HasMany($this, $related, $foreignKey, $localKey);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
