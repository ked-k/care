<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $fillable = ['key', 'value'];

    /**
     * Store a value (will json_encode non-scalar data)
     */
    public static function set(string $key, $value): void
    {
        $payload = is_scalar($value) ? (string)$value : json_encode($value);
        static::updateOrCreate(['key' => $key], ['value' => $payload]);
    }

    /**
     * Get a value; decode JSON when possible
     */
    public static function get(string $key, $default = null)
    {
        $row = static::where('key', $key)->first();
        if (! $row) return $default;
        $value = $row->value;
        // try json decode
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
