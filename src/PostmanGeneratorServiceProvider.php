<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Support\ServiceProvider;

class PostmanGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/api-postman.php' => config_path('api-postman.php'),
            ], 'postman-config');
        }

        $this->commands(ExportPostman::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/api-postman.php', 'api-postman'
        );
    }
}
