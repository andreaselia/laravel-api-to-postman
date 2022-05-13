<?php

namespace AndreasElia\PostmanGenerator\Contracts;

interface AuthenticationMethod
{
    public function resolve(): array;
}