<?php

namespace App\Services;

use App\Models\AttendanceSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * AttendanceSettingsService
 *
 * Provides a cached, typed interface for reading and writing attendance settings.
 * All business logic that needs a setting should use this service rather than
 * reading from config() or .env directly.
 *
 * Cache strategy: settings are cached for 5 minutes (CACHE_TTL).
 * Call flush() after any write to ensure the next read picks up the new value.
 */
class AttendanceSettingsService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY = 'attendance_settings.all';

    // ─── Read API ────────────────────────────────────────────────────────────────

    /**
     * Get a setting value as a raw string (or null if not set).
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->loadAll();

        if (! array_key_exists($key, $all)) {
            return $default;
        }

        $setting = $all[$key];

        return $this->cast($setting['value'], $setting['type'], $default);
    }

    public function getString(string $key, string $default = ''): string
    {
        return (string) $this->get($key, $default);
    }

    public function getInt(string $key, int $default = 0): int
    {
        return (int) $this->get($key, $default);
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Returns a Carbon instance for a time setting (e.g. "07:30"),
     * set to today's date in the application timezone.
     */
    public function getTime(string $key, ?string $default = null): ?Carbon
    {
        $value = $this->getString($key, $default ?? '');

        if (! $value) {
            return null;
        }

        return Carbon::createFromFormat('H:i', $value, config('app.timezone'));
    }

    public function getJson(string $key, array $default = []): array
    {
        $value = $this->getString($key);

        if (! $value) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    /**
     * Get all settings grouped by their group key.
     * Used by the admin settings page to render grouped form fields.
     *
     * @return array<string, array<string, array>> ['general' => ['key' => setting, ...], ...]
     */
    public function getAllGrouped(): array
    {
        $grouped = [];

        foreach ($this->loadAll() as $key => $setting) {
            $group = $setting['group'] ?? 'general';
            $grouped[$group][$key] = $setting;
        }

        return $grouped;
    }

    // ─── Write API ───────────────────────────────────────────────────────────────

    /**
     * Persist a setting value and flush the cache.
     */
    public function set(string $key, mixed $value, int $updatedBy): void
    {
        AttendanceSetting::where('key', $key)->update([
            'value'      => is_array($value) ? json_encode($value) : (string) $value,
            'updated_by' => $updatedBy,
            'updated_at' => now(),
        ]);

        $this->flush();
    }

    /**
     * Persist multiple settings at once and flush the cache once.
     *
     * @param array<string, mixed> $settings
     */
    public function setMany(array $settings, int $updatedBy): void
    {
        foreach ($settings as $key => $value) {
            AttendanceSetting::where('key', $key)->update([
                'value'      => is_array($value) ? json_encode($value) : (string) $value,
                'updated_by' => $updatedBy,
                'updated_at' => now(),
            ]);
        }

        $this->flush();
    }

    /**
     * Clear the settings cache. Call this after any write.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─── Internal ────────────────────────────────────────────────────────────────

    /**
     * Load all settings from cache or database.
     * Returns a flat associative array keyed by setting key.
     *
     * @return array<string, array{value: ?string, type: string, group: string, label: string}>
     */
    private function loadAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return AttendanceSetting::all()
                ->keyBy('key')
                ->map(fn ($s) => [
                    'value'       => $s->value,
                    'type'        => $s->type,
                    'group'       => $s->group,
                    'label'       => $s->label,
                    'description' => $s->description,
                ])
                ->all();
        });
    }

    private function cast(mixed $value, string $type, mixed $default): mixed
    {
        if ($value === null) {
            return $default;
        }

        return match ($type) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json', 'array' => json_decode($value, true) ?? $default,
            default   => $value,
        };
    }
}
