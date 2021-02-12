<?php

namespace AndreasElia\PostmanGenerator\Tests\Feature;

class ExportPostmanTest extends \AndreasElia\PostmanGenerator\Tests\TestCase
{
    public function test_standard_export_works()
    {
        $this->artisan('export:postman')
            ->assertExitCode(0);

        // ensure output contains json x
    }

    public function test_bearer_export_works()
    {
        $this->artisan('export:postman --bearer=1234567890')
            ->assertExitCode(0);

        $this->assertTrue(true);

        // ensure output contains json x

        // ensure output has headers and variable json
    }

    public function test_structured_export_works()
    {
        // set structured to true in config

        $this->artisan('export:postman')
            ->assertExitCode(0);

        $this->assertTrue(true);

        // ensure output contains json x
    }
}
