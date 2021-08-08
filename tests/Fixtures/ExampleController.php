<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class ExampleController extends Controller
{
    public function index(): string
    {
        return 'index';
    }

    public function show(): string
    {
        return 'show';
    }

    public function store(): string
    {
        return 'store';
    }

    public function delete(): string
    {
        return 'delete';
    }

    public function showWithReflectionMethod(ExampleService $service): array
    {
        return $service->getRequestData();
    }
}
