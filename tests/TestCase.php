<?php

namespace AndreasElia\PostmanGenerator\Tests;

use AndreasElia\Tests\Stubs\ExampleController;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['AndreasElia\PostmanGenerator\PostmanGeneratorServiceProvider'];
    }

    protected function defineRoutes($router)
    {
        $router->middleware('api')->group(function ($router) {
            $router->get('index', [ExampleController::class, 'index']);
            $router->get('show', [ExampleController::class, 'show']);
            $router->post('store', [ExampleController::class, 'store']);
            $router->delete('delete', [ExampleController::class, 'delete']);
        });
    }
}
