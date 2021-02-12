<?php

namespace AndreasElia\PostmanGenerator\Tests\Feature;

use Illuminate\Support\Facades\Artisan;

class ExportPostmanTest extends \Tests\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function test_standard_export_works()
    {
        Artisan::call('export:postman');

        // get output

        // ensure output contains json x
    }

    protected function test_bearer_export_works()
    {
        Artisan::call('export:postman --bearer=1234567890');

        // get output

        // ensure output contains json x

        // ensure output has headers and variable json
    }

    protected function test_structured_export_works()
    {
        // set structured to true in config

        Artisan::call('export:postman');

        // get output

        // ensure output contains json x
    }
}
