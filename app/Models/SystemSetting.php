<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    public const CREATED_AT = null;
    public const UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * Retrieve a setting value by key, or return default if not present.
     */
    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Retrieve a numeric setting value (float or int).
     */
    public static function getNumeric(string $key, float|int $default = 0): float
    {
        $val = static::getValue($key);

        return $val !== null ? (float) $val : (float) $default;
    }

    /**
     * Set or update a configuration setting value by key.
     */
    public static function setValue(string $key, mixed $value, ?string $description = null): self
    {
        $attributes = ['value' => (string) $value];

        if ($description !== null) {
            $attributes['description'] = $description;
        }

        return static::updateOrCreate(
            ['key' => $key],
            $attributes
        );
    }
}
