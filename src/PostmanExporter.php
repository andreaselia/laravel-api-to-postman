<?php

namespace AndreasElia\PostmanGenerator;

use AndreasElia\PostmanGenerator\Authentication\AuthenticationMethod;
use Illuminate\Config\Repository;
use Illuminate\Support\Str;

class PostmanExporter
{
    public string $filename;

    protected array $structure;

    protected Repository $config;

    protected ?AuthenticationMethod $authentication = null;

    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;

        return $this;
    }

    public function getStructure()
    {
        return json_encode($this->structure);
    }

    public function export()
    {
        $this->resolveAuth();

        dd($this->authentication);
    }

    public function resolveAuth(): self
    {
        $config = $this->config->get('api-postman.authentication');

        if ($config['method']) {
            $className = Str::of(__NAMESPACE__.'\\Authentication\\')
                ->append(ucfirst($config['method']))
                ->toString();

            $this->authentication = new $className;
        }

        return $this;
    }
}
