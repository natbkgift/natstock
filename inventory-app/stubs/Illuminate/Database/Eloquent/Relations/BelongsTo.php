<?php

namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class BelongsTo
{
    public function __construct(
        private readonly Model $parent,
        private readonly string $related,
        private readonly ?string $foreignKey = null,
        private readonly ?string $ownerKey = null
    ) {
    }

    public function getRelated(): string
    {
        return $this->related;
    }
}
