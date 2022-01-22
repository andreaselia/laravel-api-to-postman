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
    public function test_basic_export_works(bool $formDataEnabled)
    {
        config()->set('api-postman.enable_formdata', $formDataEnabled);

        $this->artisan('export:postman --basic=username:password1234')->assertExitCode(0);

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes();

        $collectionVariables = $collection['variable'];

        foreach ($collectionVariables as $variable) {
            if ($variable['key'] != 'token') {
                continue;
            }

            $this->assertEquals($variable['value'], 'username:password1234');
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

    public function test_rules_printing_export_works()
    {
        config([
            'api-postman.enable_formdata' => true,
            'api-postman.print_rules' => true,
            'api-postman.rules_to_human_readable' => false,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $this->assertTrue(true);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/storeWithFormRequest')
            ->first();

        $fields = collect($targetRequest['request']['body']['urlencoded']);
        $this->assertCount(1, $fields->where('key', 'field_1')->where('description', 'required'));
        $this->assertCount(1, $fields->where('key', 'field_2')->where('description', 'required, integer'));
        $this->assertCount(1, $fields->where('key', 'field_5')->where('description', 'required, integer, max:30, min:1'));
        $this->assertCount(1, $fields->where('key', 'field_6')->where('description', 'in:"1","2","3"'));
    }

    public function test_rules_printing_export_to_human_readable_works()
    {
        config([
            'api-postman.enable_formdata' => true,
            'api-postman.print_rules' => true,
            'api-postman.rules_to_human_readable' => true,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $this->assertTrue(true);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/storeWithFormRequest')
            ->first();

        $fields = collect($targetRequest['request']['body']['urlencoded']);
        $this->assertCount(1, $fields->where('key', 'field_1')->where('description', 'The field 1 field is required.'));
        $this->assertCount(1, $fields->where('key', 'field_2')->where('description', 'The field 2 field is required., The field 2 must be an integer.'));
        $this->assertCount(1, $fields->where('key', 'field_3')->where('description', '(Optional), The field 3 must be an integer.'));
        $this->assertCount(1, $fields->where('key', 'field_4')->where('description', '(Nullable), The field 4 must be an integer.'));
        $this->assertCount(1, $fields->where('key', 'field_5')->where('description', 'The field 5 field is required., The field 5 must be an integer., The field 5 must not be greater than 30., The field 5 must be at least 1.'));

        /** This looks bad, but this is the default message in lang/en/validation.php, you can update to:.
         *
         * "'in' => 'The selected :attribute is invalid. Allowable values: :values',"
         **/
        $this->assertCount(1, $fields->where('key', 'field_6')->where('description', 'The selected field 6 is invalid.'));
    }

    public function test_export_with_request_description_works()
    {
        config([
            'api-postman.enable_formdata' => false,
            'api-postman.extract_description_from_controller' => true,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $this->assertTrue(true);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $deprecationRequest = $collection
            ->where('name', 'example/show')
            ->first();
        $this->assertSame('### This URI is deprecated.', $deprecationRequest['request']['description']);

        $plannedDeprecationRequest = $collection
            ->where('name', 'example/store')
            ->first();
        $this->assertSame('### This URI is planned to be deprecated.', $plannedDeprecationRequest['request']['description']);

        $targetRequest = $collection
            ->where('name', 'example/storeWithFormRequest')
            ->first();

        $this->assertSame("We want to extract this text and nothing else.\r", $targetRequest['request']['description']);

        $multiLinedRequest = $collection
            ->where('name', 'example/delete')
            ->first();

        $this->assertEquals("We want to extract this text and the next line\rThis is the second line we are extracting to show it works multilines\r", $multiLinedRequest['request']['description']);
    }

    public function test_export_with_disabled_request_description_works()
    {
        config([
            'api-postman.enable_formdata' => false,
            'api-postman.extract_description_from_controller' => false,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $this->assertTrue(true);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/storeWithFormRequest')
            ->first();

        $this->assertSame($targetRequest['request']['description'], '');

        $multiLinedRequest = $collection
            ->where('name', 'example/delete')
            ->first();

        $this->assertSame($multiLinedRequest['request']['description'], '');
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
