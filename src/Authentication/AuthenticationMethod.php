<?php

namespace AndreasElia\PostmanGenerator\Authentication;

use Illuminate\Contracts\Support\Arrayable;

abstract class AuthenticationMethod implements Arrayable
{
    public function toArray(): array
    {
        return [
            'key' => 'Authorization',
            'value' => sprintf('%s {{token}}', $this->prefix()),
        ];
    }

    abstract public function prefix(): string;
}
