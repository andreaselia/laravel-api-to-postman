<?php

namespace AndreasElia\PostmanGenerator\Concerns;

use AndreasElia\PostmanGenerator\Authentication\AuthenticationMethod;
use Illuminate\Support\Str;
use InvalidArgumentException;

trait HasAuthentication
{
    protected ?AuthenticationMethod $authentication = null;

    public function resolveAuth(): self
    {
        $config = $this->config['authentication'];

        if ($config['method']) {
            $className = Str::of('AndreasElia\\PostmanGenerator\\Authentication\\')
                ->append(ucfirst(strtolower($config['method'])))
                ->toString();

            if (! is_subclass_of($className, AuthenticationMethod::class)) {
                throw new InvalidArgumentException("Unsupported authentication method [{$config['method']}], use bearer or basic.");
            }

            $this->authentication = new $className($config['token']);
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
