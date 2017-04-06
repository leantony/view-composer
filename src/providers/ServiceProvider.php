<?php

namespace Leantony\ViewComposer\Providers;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/view_composers.php' => config_path('view_composers.php')
        ], 'config');
    }
}