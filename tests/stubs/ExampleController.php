<?php

namespace AndreasElia\Tests\Stubs;

use App\Http\Controllers\Controller;

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
}
