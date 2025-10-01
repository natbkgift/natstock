<?php

namespace Symfony\Component\HttpFoundation;

class StreamedResponse
{
    public object $headers;

    /** @var callable */
    private $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
        $this->headers = new class
        {
            /** @var array<string, string> */
            private array $values = [];

            public function set(string $name, string $value): void
            {
                $this->values[$name] = $value;
            }

            public function get(string $name): ?string
            {
                return $this->values[$name] ?? null;
            }
        };
    }

    public function sendContent(): void
    {
        ($this->callback)();
    }
}
