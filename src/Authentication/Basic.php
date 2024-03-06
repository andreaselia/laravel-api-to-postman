<?php

namespace AndreasElia\PostmanGenerator\Authentication;

class Basic extends AuthenticationMethod
{
    public function prefix(): string
    {
        return 'Basic';
    }
}
