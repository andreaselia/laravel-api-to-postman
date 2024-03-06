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

    public function storeWithFormRequest(ExampleFormRequest $request): string
    {
        return 'storeWithFormRequest';
    }

    public function getWithFormRequest(ExampleFormRequest $request): string
    {
        return 'getWithFormRequest';
    }

    /**
     * This is the php doc route.
     * Which is also multi-line.
     *
     * and has a blank line.
     *
     * @param  string  $non-existing  param
     */
    public function phpDocRoute(): string
    {
        return 'phpDocRoute';
    }
}
