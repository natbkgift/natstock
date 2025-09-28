<?php

namespace Illuminate\Database\Eloquent\Factories;

trait HasFactory
{
    public static function factory(): object
    {
        $class = static::class;
        $parts = explode('\\', $class);
        $short = end($parts);
        $factoryClass = 'Database\\Factories\\' . $short . 'Factory';

        if (!class_exists($factoryClass)) {
            throw new \RuntimeException("Factory {$factoryClass} not found for model {$class}.");
        }

        return new $factoryClass();
    }
}
