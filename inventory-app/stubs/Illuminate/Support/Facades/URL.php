<?php

namespace Illuminate\Support\Facades;

use DateTimeInterface;

class URL
{
    public static function temporarySignedRoute(string $name, DateTimeInterface $expiration, array $parameters = []): string
    {
        $timestamp = $expiration->format(DateTimeInterface::ATOM);
        $query = $parameters === [] ? '' : http_build_query($parameters);

        return sprintf('signed-route:%s:%s:%s', $name, $timestamp, $query);
    }
}
