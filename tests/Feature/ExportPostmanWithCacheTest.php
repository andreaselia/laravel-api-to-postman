<?php

namespace AndreasElia\PostmanGenerator\Tests\Feature;

use AndreasElia\PostmanGenerator\Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ExportPostmanWithCacheTest extends TestCase
{
    use \Orchestra\Testbench\Concerns\HandlesRoutes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defineCacheRoutes(<<<'PHP'
<?php
Route::middleware('api')->group(function () {
    Route::get('serialized-route', function () {
        return 'Serialized Route';
    });
});
PHP);

        config()->set('api-postman.filename', 'test.json');

        Storage::disk()->deleteDirectory('postman');
    }

    public function test_cached_export_works()
    {
        $this->get('serialized-route')
            ->assertOk()
            ->assertSee('Serialized Route');

        $this->artisan('export:postman')->assertExitCode(0);

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes();

        $collectionItems = $collection['item'];

        $this->assertCount(count($routes), $collectionItems);

        foreach ($routes as $route) {
            $collectionRoute = Arr::first($collectionItems, function ($item) use ($route) {
                return $item['name'] == $route->uri();
            });
            $this->assertNotNull($collectionRoute);
            $this->assertTrue(in_array($collectionRoute['request']['method'], $route->methods()));
        }
    }
}
