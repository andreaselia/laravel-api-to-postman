<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Support\ServiceProvider;

class PostmanGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
              __DIR__ . '/../config/api-postman.php' => config_path('api-postman.php'),
            ], 'config');
        }

        $this->commands(ExportPostman::class);
    }
}
