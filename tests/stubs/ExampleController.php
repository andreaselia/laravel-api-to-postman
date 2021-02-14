<?php

namespace AndreasElia\Tests\Stubs;

use App\Http\Controllers\Controller;

class ExampleController extends Controller
{
    public function index() {
        return 'index';
    }

    public function show() {
        return 'show';
    }

    public function store() {
        return 'store';
    }

    public function delete() {
        return 'delete';
    }
}
