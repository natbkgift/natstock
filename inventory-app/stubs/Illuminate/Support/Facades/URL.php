<?php

namespace Illuminate\Support\Facades;

class URL
{
    public static function temporarySignedRoute(string $name, $expiration, array $parameters = []): string
    {
        $path = '/' . str_replace('.', '/', $name);

        if (str_contains($path, '/admin/import/errors/download')) {
            $token = $parameters['token'] ?? '';
            $path = '/admin/import/error/' . $token;
        }

        $parameters['signature'] = 'stub-signature';

        return $path . '?' . http_build_query($parameters);
    }
}
