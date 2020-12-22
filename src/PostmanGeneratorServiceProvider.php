<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Support\ServiceProvider;

class PostmanGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands(ExportPostman::class);
    }
}
