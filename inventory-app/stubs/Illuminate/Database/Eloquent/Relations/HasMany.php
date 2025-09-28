<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class HasMany
{
    public function __construct(
        private readonly Model $parent,
        private readonly string $related,
        private readonly ?string $foreignKey = null,
        private readonly ?string $localKey = null
    ) {
    }

    public function getRelated(): string
    {
        return $this->related;
    }
}
