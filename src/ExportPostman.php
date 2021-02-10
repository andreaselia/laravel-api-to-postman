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

                $request = $this->makeItem($route, $method);

                if ($structured) {
                    $routeNames = $route->action['as'] ?? null;
                    $routeNames = explode('.', $routeNames);
                    $routeNames = array_filter($routeNames, function ($value) {
                        return ! is_null($value) && $value !== '';
                    });

                    $destination = end($routeNames);

                    $this->generateCollectionStructure($this->structure, $routeNames, $request, $destination);
                } else {
                    $this->structure['item'][] = $request;
                }
            }
        }

        Storage::put($exportName = "$filename.json", json_encode($this->structure));

        $this->info("Postman Collection Exported: $exportName");
    }

    /**
     * @param  array  $structure
     * @param  array  $segments
     * @param  array  $request
     * @param  string  $destination
     * @return \void
     */
    protected function generateCollectionStructure(array &$structure, array $segments, array $request, string $destination): void {
        $nestingStructure = &$structure;

        foreach ($segments as $segment) {
            $matched = false;

            foreach ($nestingStructure['item'] as &$item) {
                if ($item['name'] === $segment) {
                    $nestingStructure = &$item;

                    if ($segment === $destination) {
                        $nestingStructure['item'][] = $request;
                    }

                    $matched = true;

                    break;
                }
            }

            unset($item);

            if (! $matched) {
                $item = [
                    'name' => $segment,
                    'item' => [$request],
                ];

                $nestingStructure['item'][] = &$item;
                $nestingStructure = &$item;
            }

            unset($item);
        }
    }

    /**
     * @param  \Illuminate\Routing\Route  $route
     * @param  string  $method
     * @return \array
     */
    public function makeItem(Route $route, string $method): array
    {
        return [
            'name' => $route->uri(),
            'request' => [
                'method' => strtoupper($method),
                'header' => $this->configureHeaders($route->gatherMiddleware()),
                'url' => [
                    'raw' => '{{base_url}}/'.$route->uri(),
                    'host' => '{{base_url}}/'.$route->uri(),
                ],
            ],
        ];
    }

    /**
     * @param  string  $filename
     * @return \void
     */
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

    /**
     * @return \void
     */
    protected function generateBearer(): void
    {
        $this->structure['variable'][] = [
            'key' => 'token',
            'value' => $bearer,
        ];
    }

    /**
     * @param  array  $middleware
     * @return \string[][]
     */
    protected function configureHeaders(array $middleware): array
    {
        $headers = [
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
}
