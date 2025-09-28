<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingManager
{
    private const CACHE_KEY = 'app_settings_cache';

    public function getString(string $key, ?string $default = null): string
    {
        $value = $this->getAll()[$key] ?? null;

        if ($value === null) {
            $defaults = config('inventory.settings_defaults', []);
            return (string) ($defaults[$key] ?? $default ?? '');
        }

        return (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getString($key, $default ? '1' : '0');

        $normalized = strtolower((string) $value);

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * @return array<int, string>
     */
    public function getArray(string $key, string $separator = ','): array
    {
        $raw = $this->getString($key, '');
        $items = array_filter(array_map('trim', explode($separator, $raw)), fn ($item) => $item !== '');

        return array_values($items);
    }

    public function setString(string $key, ?string $value): void
    {
        $value = $value ?? '';

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        $this->forgetCache();
    }

    public function setBool(string $key, bool $value): void
    {
        $this->setString($key, $value ? '1' : '0');
    }

    public function setArray(string $key, array $values, string $separator = ','): void
    {
        $clean = array_map(fn ($value) => trim((string) $value), $values);
        $clean = array_filter($clean, fn ($value) => $value !== '');
        $this->setString($key, implode($separator, $clean));
    }

    /**
     * @return array<int, string>
     */
    public function getNotifyChannels(): array
    {
        $channels = $this->getArray('notify_channels');

        $channels = array_unique($channels);

        return array_values($channels);
    }

    public function shouldNotifyLowStock(): bool
    {
        return $this->getBool('notify_low_stock', true);
    }

    /**
     * @return array<int, string>
     */
    public function getNotifyEmails(): array
    {
        return $this->getArray('notify_emails');
    }

    /**
     * @return array<string, string|null>
     */
    public function getAll(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $rows = Setting::query()->pluck('value', 'key')->all();
            $defaults = config('inventory.settings_defaults', []);

            return array_merge($defaults, $rows);
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function getExpiringDays(): array
    {
        $values = $this->getArray('alert_expiring_days');

        $numbers = array_map(static fn (string $value): int => (int) $value, $values);
        $numbers = array_filter($numbers, static fn (int $day): bool => $day > 0);

        sort($numbers);

        return array_values($numbers);
    }
}
