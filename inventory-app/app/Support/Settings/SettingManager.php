<?php

namespace App\Support\Settings;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingManager
{
    private const CACHE_KEY = 'app_settings_cache';
    private const RAW_CACHE_KEY = 'app_settings_cache_raw';

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

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->getString($key, (string) $default);
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
        if ($this->hasSetting('low_stock_enabled')) {
            return $this->getBool('low_stock_enabled', true);
        }

        return $this->getBool('notify_low_stock', true);
    }

    public function isExpiringAlertEnabled(): bool
    {
        return $this->getBool('expiring_enabled', true);
    }

    public function getExpiringLeadDays(): int
    {
        $days = $this->getInt('expiring_days', 30);

        return $days > 0 ? $days : 30;
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
            $rows = $this->getStoredSettings();
            $defaults = config('inventory.settings_defaults', []);

            return array_merge($defaults, $rows);
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::RAW_CACHE_KEY);
    }

    private function hasSetting(string $key): bool
    {
        return array_key_exists($key, $this->getStoredSettings());
    }

    public function getExpiringDays(): array
    {
        $values = $this->getArray('alert_expiring_days');

        $numbers = array_map(static fn (string $value): int => (int) $value, $values);
        $numbers = array_filter($numbers, static fn (int $day): bool => $day > 0);

        sort($numbers);

        return array_values($numbers);
    }

    /**
     * @return array<string, string|null>
     */
    private function getStoredSettings(): array
    {
        return Cache::rememberForever(self::RAW_CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });
    }
}
