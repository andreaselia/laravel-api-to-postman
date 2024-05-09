<?php

namespace AndreasElia\PostmanGenerator\Tests\Feature;

use AndreasElia\PostmanGenerator\Tests\Fixtures\CollectionHelpersTrait;
use AndreasElia\PostmanGenerator\Tests\TestCase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ExportPostmanTest extends TestCase
{
    use CollectionHelpersTrait;

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

        $totalCollectionItems = $this->countCollectionItems($collection['item']);

        $this->assertEquals(count($routes), $totalCollectionItems);

        foreach ($routes as $route) {
            $methods = $route->methods();

            $collectionRoutes = Arr::where($collectionItems, function ($item) use ($route) {
                return $item['name'] == $route->uri();
            });

            $collectionRoute = Arr::first($collectionRoutes);

            if (! in_array($collectionRoute['request']['method'], $methods)) {
                $methods = collect($collectionRoutes)->pluck('request.method')->toArray();
            }

            $this->assertNotNull($collectionRoute);
            $this->assertTrue(in_array($collectionRoute['request']['method'], $methods));
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

        $totalCollectionItems = $this->countCollectionItems($collection['item']);

        $this->assertEquals(count($routes), $totalCollectionItems);

        foreach ($routes as $route) {
            $methods = $route->methods();

            $collectionRoutes = Arr::where($collection['item'], function ($item) use ($route) {
                return $item['name'] == $route->uri();
            });

            $collectionRoute = Arr::first($collectionRoutes);

            if (! in_array($collectionRoute['request']['method'], $methods)) {
                $methods = collect($collectionRoutes)->pluck('request.method')->toArray();
            }

            $this->assertNotNull($collectionRoute);
            $this->assertTrue(in_array($collectionRoute['request']['method'], $methods));
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

        $totalCollectionItems = $this->countCollectionItems($collection['item']);

        $this->assertEquals(count($routes), $totalCollectionItems);

        foreach ($routes as $route) {
            $methods = $route->methods();

            $collectionRoutes = Arr::where($collection['item'], function ($item) use ($route) {
                return $item['name'] == $route->uri();
            });

            $collectionRoute = Arr::first($collectionRoutes);

            if (! in_array($collectionRoute['request']['method'], $methods)) {
                $methods = collect($collectionRoutes)->pluck('request.method')->toArray();
            }

            $this->assertNotNull($collectionRoute);
            $this->assertTrue(in_array($collectionRoute['request']['method'], $methods));
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

        $collection = json_decode(Storage::get('postman/'.config('api-postman.filename')), true);

        $routes = $this->app['router']->getRoutes();

        $totalCollectionItems = $this->countCollectionItems($collection['item']);

        $this->assertEquals(count($routes), $totalCollectionItems);
    }

    public function test_rules_printing_export_works()
    {
        config([
            'api-postman.enable_formdata' => true,
            'api-postman.print_rules' => true,
            'api-postman.rules_to_human_readable' => false,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

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

    public function test_rules_printing_get_export_works()
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
            ->where('name', 'example/getWithFormRequest')
            ->first();

        $this->assertEqualsCanonicalizing([
            'raw' => '{{base_url}}/example/getWithFormRequest',
            'host' => [
                '{{base_url}}',
            ],
            'path' => [
                'example',
                'getWithFormRequest',
            ],
            'variable' => [],
        ], array_slice($targetRequest['request']['url'], 0, 4));

        $fields = collect($targetRequest['request']['url']['query']);
        $this->assertCount(1, $fields->where('key', 'field_1')->where('description', 'required'));
        $this->assertCount(1, $fields->where('key', 'field_2')->where('description', 'required, integer'));
        $this->assertCount(1, $fields->where('key', 'field_5')->where('description', 'required, integer, max:30, min:1'));
        $this->assertCount(1, $fields->where('key', 'field_6')->where('description', 'in:"1","2","3"'));

        // Check for the required structure of the get request query
        foreach ($fields as $field) {
            $this->assertEqualsCanonicalizing([
                'key' => $field['key'],
                'value' => null,
                'disabled' => false,
                'description' => $field['description'],
            ], $field);
        }
    }

    public function test_rules_printing_export_to_human_readable_works()
    {
        config([
            'api-postman.enable_formdata' => true,
            'api-postman.print_rules' => true,
            'api-postman.rules_to_human_readable' => true,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/storeWithFormRequest')
            ->first();

        $fields = collect($targetRequest['request']['body']['urlencoded']);
        $this->assertCount(1, $fields->where('key', 'field_1')->where('description', 'The field 1 field is required.'));
        $this->assertCount(1, $fields->where('key', 'field_2')->where('description', 'The field 2 field is required., The field 2 field must be an integer.'));
        $this->assertCount(1, $fields->where('key', 'field_3')->where('description', '(Optional), The field 3 field must be an integer.'));
        $this->assertCount(1, $fields->where('key', 'field_4')->where('description', '(Nullable), The field 4 field must be an integer.'));
        // the below fails locally, but passes on GitHub actions?
        $this->assertCount(1, $fields->where('key', 'field_5')->where('description', 'The field 5 field is required., The field 5 field must be an integer., The field 5 field must not be greater than 30., The field 5 field must be at least 1.'));

        /** This looks bad, but this is the default message in lang/en/validation.php, you can update to:.
         *
         * "'in' => 'The selected :attribute is invalid. Allowable values: :values',"
         **/
        $this->assertCount(1, $fields->where('key', 'field_6')->where('description', 'The selected field 6 is invalid.'));
        $this->assertCount(1, $fields->where('key', 'field_7')->where('description', 'The field 7 field is required.'));
        $this->assertCount(1, $fields->where('key', 'field_8')->where('description', 'The field 8 field must be uppercase.'));
        $this->assertCount(1, $fields->where('key', 'field_9')->where('description', 'The field 9 field is required., The field 9 field must be a string.'));
    }

    public function test_event_export_works()
    {
        $eventScriptPath = 'tests/Fixtures/ExampleEvent.js';

        config([
            'api-postman.prerequest_script' => $eventScriptPath,
            'api-postman.test_script' => $eventScriptPath,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['event']);

        $events = $collection
            ->whereIn('listen', ['prerequest', 'test'])
            ->all();

        $this->assertCount(2, $events);

        $content = file_get_contents($eventScriptPath);

        foreach ($events as $event) {
            $this->assertEquals($event['script']['exec'], $content);
        }
    }

    public function test_php_doc_comment_export()
    {
        config([
            'api-postman.include_doc_comments' => true,
        ]);

        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/phpDocRoute')
            ->first();

        $this->assertEquals($targetRequest['request']['description'], 'This is the php doc route. Which is also multi-line. and has a blank line.');
    }

    public function test_uri_is_correct()
    {
        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/phpDocRoute')
            ->first();

        $this->assertEquals($targetRequest['name'], 'example/phpDocRoute');
        $this->assertEquals($targetRequest['request']['url']['raw'], '{{base_url}}/example/phpDocRoute');
    }

    public function test_api_resource_routes_set_parameters_correctly_with_hyphens()
    {
        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/users/{user}/audit-logs/{audit_log}')
            ->where('request.method', 'PATCH')
            ->first();

        $this->assertEquals($targetRequest['name'], 'example/users/{user}/audit-logs/{audit_log}');
        $this->assertEquals($targetRequest['request']['url']['raw'], '{{base_url}}/example/users/:user/audit-logs/:audit_log');
    }

    public function test_api_resource_routes_set_parameters_correctly_with_underscores()
    {
        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/users/{user}/other_logs/{other_log}')
            ->where('request.method', 'PATCH')
            ->first();

        $this->assertEquals($targetRequest['name'], 'example/users/{user}/other_logs/{other_log}');
        $this->assertEquals($targetRequest['request']['url']['raw'], '{{base_url}}/example/users/:user/other_logs/:other_log');
    }

    public function test_api_resource_routes_set_parameters_correctly_with_camel_case()
    {
        $this->artisan('export:postman')->assertExitCode(0);

        $collection = collect(json_decode(Storage::get('postman/'.config('api-postman.filename')), true)['item']);

        $targetRequest = $collection
            ->where('name', 'example/users/{user}/someLogs/{someLog}')
            ->where('request.method', 'PATCH')
            ->first();

        $this->assertEquals($targetRequest['name'], 'example/users/{user}/someLogs/{someLog}');
        $this->assertEquals($targetRequest['request']['url']['raw'], '{{base_url}}/example/users/:user/someLogs/:someLog');
    }

    public static function providerFormDataEnabled(): array
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
