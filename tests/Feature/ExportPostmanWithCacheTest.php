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
        $this->markTestSkipped('Vendor routes are included in the cached routes, so this test fails');

        $this->get('serialized-route')
            ->assertOk()
            ->assertSee('Serialized Route');

        $this->artisan('export:postman')->assertExitCode(0);

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes()->getRoutesByName();

        // Filter out workbench routes from orchestra/workbench
        $routes = array_filter($routes, function ($route) {
            return strpos($route->uri(), 'workbench') === false;
        });

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
