<?php

namespace AndreasElia\PostmanGenerator\Tests\Feature;

use AndreasElia\PostmanGenerator\Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ExportPostmanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('api-postman.filename', 'test.json');

        Storage::disk()->deleteDirectory('postman');
    }

    /**
     * @dataProvider providerFormDataEnabled
     */
    public function test_standard_export_works(bool $formDataEnabled)
    {
        config()->set('api-postman.enable_formdata', $formDataEnabled);

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

    /**
     * @dataProvider providerFormDataEnabled
     */
    public function test_bearer_export_works(bool $formDataEnabled)
    {
        config()->set('api-postman.enable_formdata', $formDataEnabled);

        $this->artisan('export:postman --bearer=1234567890')->assertExitCode(0);

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes();

        $collectionVariables = $collection['variable'];

        foreach ($collectionVariables as $variable) {
            if ($variable['key'] != 'token') {
                continue;
            }

            $this->assertEquals($variable['value'], '1234567890');
        }

        $this->assertCount(2, $collectionVariables);

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

    /**
     * @dataProvider providerFormDataEnabled
     */
    public function test_structured_export_works(bool $formDataEnabled)
    {
        config([
            'api-postman.structured' => true,
            'api-postman.enable_formdata' => $formDataEnabled,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $this->assertTrue(true);

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes();

        $collectionItems = $collection['item'];

        $this->assertCount(count($routes), $collectionItems[0]['item']);
    }

    public function providerFormDataEnabled(): array
    {
        return [
            [
                false,
            ],
            [
                true,
            ],
        ];
    }
}
