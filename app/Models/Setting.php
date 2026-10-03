<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Small key/value store for things set from the admin area.
 * Values are encrypted at rest with the app key.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return ['value' => 'encrypted'];
    }

    public static function read(string $key): ?string
    {
        return static::find($key)?->value;
    }

    public static function write(string $key, ?string $value): void
    {
        blank($value)
            ? static::whereKey($key)->delete()
            : static::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("setting.{$key}");
    }

    /**
     * Read a setting through the cache, for values every public page view needs.
     */
    public static function cached(string $key): ?string
    {
        return Cache::rememberForever("setting.{$key}", fn () => (string) static::read($key)) ?: null;
    }

    /**
     * The GA4 measurement ID (G-XXXX) for the public tracking snippet.
     */
    public static function measurementId(): ?string
    {
        return static::cached('ga_measurement_id');
    }
}
