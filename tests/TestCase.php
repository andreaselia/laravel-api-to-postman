<?php

namespace AndreasElia\PostmanGenerator\Tests;

use AndreasElia\PostmanGenerator\Tests\Fixtures\ExampleController;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['AndreasElia\PostmanGenerator\PostmanGeneratorServiceProvider'];
    }

    protected function defineRoutes($router)
    {
        $router->middleware('api')->prefix('example')->name('example.')->group(function ($router) {
            $router->get('index', [ExampleController::class, 'index'])->name('index');
            $router->get('show', [ExampleController::class, 'show'])->name('show');
            $router->post('store', [ExampleController::class, 'store'])->name('store');
            $router->delete('delete', [ExampleController::class, 'delete'])->name('delete');
            $router->get('showWithReflectionMethod', [ExampleController::class, 'showWithReflectionMethod'])->name('show-with-reflection-method');
            $router->post('storeWithFormRequest', [ExampleController::class, 'storeWithFormRequest'])->name('store-with-form-request');
            $router->get('getWithFormRequest', [ExampleController::class, 'getWithFormRequest'])->name('get-with-form-request');
            $router->get('phpDocRoute', [ExampleController::class, 'phpDocRoute'])->name('php-doc-route');
        });
    }
}
