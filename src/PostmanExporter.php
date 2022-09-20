<?php

namespace AndreasElia\PostmanGenerator;

use AndreasElia\PostmanGenerator\Authentication\AuthenticationMethod;
use Illuminate\Support\Str;

class PostmanExporter
{
    public string $filename;

    protected array $structure;

    protected ?AuthenticationMethod $authentication = null;

    public function __construct()
    {
        $this->structure = $this->generateInitialStructure();
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
        $config = config('api-postman.authentication');

        if ($config['method']) {
            $className = Str::of(__NAMESPACE__.'\\Authentication\\')
                ->append(ucfirst($config['method']))
                ->toString();

            $this->authentication = new $className;
        }

        return $this;
    }

    protected function generateInitialStructure(): array
    {
        $structure = [
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => config('api-postman.base_url'),
                ],
            ],
            'info' => [
                'name' => $this->filename,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        if ($this->token) {
            $structure['variable'][] = [
                'key' => 'token',
                'value' => $this->token,
            ];
        }

        return $structure;
    }
}
