<?php

namespace Illuminate\Support\Facades;

class DB
{
    public static function transaction(callable $callback): mixed
    {
        return $callback();
    }
}
