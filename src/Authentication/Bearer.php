<?php

namespace AndreasElia\PostmanGenerator\Authentication;

use AndreasElia\PostmanGenerator\Contracts\AuthenticationMethod;

class Bearer implements AuthenticationMethod
{
    public function resolve(): array
    {
        return [];
    }
}
