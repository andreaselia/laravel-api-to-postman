<?php

namespace AndreasElia\PostmanGenerator\Concerns;

use AndreasElia\PostmanGenerator\Authentication\AuthenticationMethod;
use Illuminate\Support\Str;

trait HasAuthentication
{
    protected ?AuthenticationMethod $authentication = null;

    public function resolveAuth(): self
    {
        $config = $this->config['authentication'];

        if ($config['method']) {
            $className = Str::of('AndreasElia\\PostmanGenerator\\Authentication\\')
                ->append(ucfirst($config['method']))
                ->toString();

            $this->authentication = new $className;
        }

        return $this;
    }

    public function setAuthentication(?AuthenticationMethod $authentication): self
    {
        if (isset($authentication)) {
            $this->authentication = $authentication;
        }

        return $this;
    }
}
