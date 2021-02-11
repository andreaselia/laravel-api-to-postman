<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

class ExportPostman extends Command
{
    /** @var string */
    protected $signature = 'export:postman {--bearer= : The bearer token to use on your endpoints}';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    /** @var \Illuminate\Routing\Router */
    protected $router;

    /** @var array */
    protected $structure;

    /** @var array */
    protected $config;

    public function __construct(Router $router, Repository $config)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = $config['api-postman'];
    }

    public function handle(): void
    {
        $bearer = $this->option('bearer') ?? false;

        $filename = date('Y_m_d_His').'_postman';

        $this->initStructure($filename);

        $structured = $this->config['structured'];

        if ($bearer) {
            $this->generateBearer();
        }

        foreach ($this->router->getRoutes() as $route) {
            $middleware = $route->gatherMiddleware();

            foreach ($route->methods as $method) {
                if ($method == 'HEAD' || empty($middleware) || ! in_array('api', $middleware)) {
                    continue;
                }

                $request = $this->makeItem($route, $method, $middleware);

                if (true) {
                    $routeNames = $route->action['as'] ?? null;
                    $routeNames = explode('.', $routeNames);
                    $routeNames = array_filter($routeNames, function ($value) {
                        return ! is_null($value) && $value !== '';
                    });

                    $destination = end($routeNames);


                } else {
                    $this->structure['item'][] = $request;
                }
            }
        }

        Storage::put($exportName = "$filename.json", json_encode($this->structure));

//        $this->info("Postman Collection Exported: $exportName");

        dump($this->structure);
    }

    protected function initStructure(string $filename): void
    {
        $this->structure = [
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $this->config['base_url'],
                ],
            ],
            'info' => [
                'name' => $filename,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];
    }

    protected function generateBearer(): void
    {
        $this->structure['variable'][] = [
            'key' => 'token',
            'value' => $bearer,
        ];
    }

    protected function configureHeaders(array $middleware): array
    {
        $headers = [
            [
                'key' => 'Accept',
                'value' => 'application/json',
            ],
            [
                'key' => 'Content-Type',
                'value' => 'application/json',
            ],
        ];

        if ($this->option('bearer') && in_array($this->config['auth_middleware'], $middleware)) {
            $headers[] = [
                'key' => 'Authorization',
                'value' => 'Bearer {{token}}',
            ];
        }

        return $headers;
    }

    public function makeItem(Route $route, string $method): array
    {
        return [
            'name' => $route->uri(),
            'request' => [
                'method' => strtoupper($method),
                'header' => $this->configureHeaders($middleware),
                'url' => [
                    'raw' => '{{base_url}}/'.$route->uri(),
                    'host' => '{{base_url}}/'.$route->uri(),
                ],
            ],
        ];
    }
}
