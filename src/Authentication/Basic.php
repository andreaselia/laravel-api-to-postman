<?php

namespace AndreasElia\PostmanGenerator\Authentication;

use AndreasElia\PostmanGenerator\Contracts\AuthenticationMethod;

class Basic implements AuthenticationMethod
{
    public function resolve(): array
    {
        return [];
    }
}
