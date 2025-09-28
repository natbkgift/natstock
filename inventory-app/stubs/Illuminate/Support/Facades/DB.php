<?php

namespace Illuminate\Support\Facades;

use Closure;

class DB
{
    public static function transaction(Closure $callback): mixed
    {
        return $callback();
    }
}
