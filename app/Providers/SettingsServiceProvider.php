<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SettingsManager;

class SettingsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('settings', function ($app) {
            return new SettingsManager();
        });
    }

    public function boot()
    {
        // no-op
    }
}
