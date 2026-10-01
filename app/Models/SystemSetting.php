<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $table = 'system_settings';

    protected $fillable = ['key', 'value', 'type', 'description', 'is_encrypted', 'updated_by'];

    protected $casts = ['is_encrypted' => 'boolean'];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) return $default;

        return match ($setting->type) {
            'int'   => (int) $setting->value,
            'bool'  => in_array(strtolower($setting->value), ['1', 'true', 'yes', 'on'], true),
            'json'  => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        $encoded = match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => json_encode($value),
            default => (string) $value,
        };
        static::updateOrCreate(
            ['key' => $key],
            ['value' => $encoded, 'type' => $type, 'updated_by' => auth()->id()],
        );
    }
}
