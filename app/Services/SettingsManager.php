<?php

namespace App\Services;

use App\Models\Setting;

class SettingsManager
{
    public function get(string $key, $default = null)
    {
        return Setting::get($key, $default);
    }

    public function set(string $key, $value): void
    {
        Setting::set($key, $value);
    }

    public function has(string $key): bool
    {
        return Setting::get($key, null) !== null;
    }

    public function forget(string $key): void
    {
        Setting::where('key', $key)->delete();
    }
}
