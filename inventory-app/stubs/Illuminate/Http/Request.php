<?php

namespace Illuminate\Http;

use App\Models\User;

class Request
{
    /** @var array<string, mixed> */
    protected array $input = [];

    /** @var array<string, mixed> */
    protected array $files = [];

    protected ?User $user = null;

    public function __construct(array $input = [], array $files = [], ?User $user = null)
    {
        $this->input = $input;
        $this->files = $files;
        $this->user = $user;
    }

    public function merge(array $input): void
    {
        $this->input = array_merge($this->input, $input);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->input[$key] ?? $default;
    }

    public function boolean(string $key, mixed $default = false): bool
    {
        $value = $this->input($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return (bool) $value;
    }

    public function file(string $key): mixed
    {
        return $this->files[$key] ?? null;
    }

    public function hasFile(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function user(): ?User
    {
        return $this->user;
    }
}
