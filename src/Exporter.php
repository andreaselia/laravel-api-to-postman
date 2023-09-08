<?php

namespace AndreasElia\PostmanGenerator;

use AndreasElia\PostmanGenerator\Concerns\HasAuthentication;
use AndreasElia\PostmanGenerator\Processors\RouteProcessor;
use Illuminate\Contracts\Config\Repository;

class Exporter
{
    use HasAuthentication;

    protected string $filename;

    protected array $output;

    private array $config;

    public function __construct(Repository $config)
    {
        $this->config = $config['api-postman'];
    }

    public function to(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getOutput()
    {
        return json_encode($this->output);
    }

    public function export(): void
    {
        $this->resolveAuth();

        $this->output = $this->generateStructure();
    }

    protected function generateStructure(): array
    {
        $this->output = [
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $this->config['base_url'],
                ],
            ],
            'info' => [
                'name' => $this->filename,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
            'event' => [],
        ];

        $preRequestPath = $this->config['prerequest_script'];
        $testPath = $this->config['test_script'];

        if ($preRequestPath || $testPath) {
            $scripts = [
                'prerequest' => $preRequestPath,
                'test' => $testPath,
            ];

            foreach ($scripts as $type => $path) {
                if (file_exists($path)) {
                    $this->output['event'][] = [
                        'listen' => $type,
                        'script' => [
                            'type' => 'text/javascript',
                            'exec' => file_get_contents($path),
                        ],
                    ];
                }
            }
        }

        if ($this->authentication) {
            $this->output['variable'][] = [
                'key' => 'token',
                'value' => $this->authentication->getToken(),
            ];
        }

        return app(RouteProcessor::class)->process($this->output);
    }
}
