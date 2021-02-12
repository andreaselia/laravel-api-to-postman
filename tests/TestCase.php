<?php

namespace AndreasElia\PostmanGenerator\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['AndreasElia\PostmanGenerator\PostmanGeneratorServiceProvider'];
    }
}
