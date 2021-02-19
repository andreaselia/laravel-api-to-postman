<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Illuminate\Routing\Controller;

class ExampleController extends Controller
{
    public function index()
    {
        return 'index';
    }

    public function show()
    {
        return 'show';
    }

    public function store()
    {
        return 'store';
    }

    public function delete()
    {
        return 'delete';
    }
}
