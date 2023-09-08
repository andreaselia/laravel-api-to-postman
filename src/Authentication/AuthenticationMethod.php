<?php

namespace AndreasElia\PostmanGenerator\Authentication;

use Illuminate\Contracts\Support\Arrayable;

abstract class AuthenticationMethod implements Arrayable
{
    public function __construct(protected ?string $token = null)
    {
    }

    public function toArray(): array
    {
        return [
            'key' => 'Authorization',
            'value' => sprintf('%s %s', $this->prefix(), $this->token ?? '{{token}}'),
        ];
    }

    public function getToken(): string
    {
        return $this->token;
    }

    abstract public function prefix(): string;
}
