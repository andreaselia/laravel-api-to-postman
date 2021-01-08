<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;

class ExportPostman extends Command
{
    /** @var string */
    protected $signature = '
        export:postman
        {--structured= : If you want folders to be generated based on namespace}
        {--base-url= : The base URL for all of your endpoints}
        {--bearer= : The bearer token to use on your endpoints}
    ';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    /** @var array */
    protected $routes;

    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
    }

    public function handle(): void
    {
        $structured = $this->option('structured') ?? false;
        $baseUrl = $this->option('base-url') ?? 'https://api.example.com/';
        $bearer = $this->option('bearer') ?? '1|token';

        $this->routes = [
            'variable' => [
                [
                    'key' => 'base_url',
                    'value' => $baseUrl,
                ],
            ],
            'info' => [
                'name' => $filename = date('Y_m_d_His').'_postman',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        if ($bearer) {
            $this->routes['variable'][] = [
                'key' => 'token',
                'value' => $bearer,
            ];
        }

        foreach ($this->router->getRoutes() as $route) {
            $middleware = $route->middleware();

            foreach ($route->methods as $method) {
                if ($method == 'HEAD' || empty($middleware) || $middleware[0] !== 'api') {
                    continue;
                }

                $routeHeaders = [
                    [
                        'key' => 'Content-Type',
                        'value' => 'application/json',
                    ],
                ];

                if ($bearer && in_array('auth:sanctum', $middleware)) {
                    $routeHeaders[] = [
                        'key' => 'Authorization',
                        'value' => 'Bearer {{token}}',
                    ];
                }

                $request = $this->makeItem($route, $method, $routeHeaders);

                if (! $structured) {
                    $this->routes['item'][] = $request;
                }

                if ($structured) {
                    $not = ['index', 'show', 'store', 'update', 'destroy'];

                    $routeNames = $route->action['as'] ?? null;
                    $routeNames = explode('.', $routeNames);
                    $routeNames = array_filter($routeNames, function ($value) use ($not) {
                        return ! is_null($value) && $value !== '' && ! in_array($value, $not);
                    });

                    $destination = end($routeNames);

                    $this->ensurePath($this->routes, $routeNames, $request, $destination);
                }
            }
        }

        Storage::put($exportName = "$filename.json", json_encode($this->routes));

        $this->info("Postman Collection Exported: $exportName");
    }

    protected function ensurePath(array &$root, array $segments, array $request, string $destination): void
    {
        $parent = &$root;

        foreach ($segments as $segment) {
            $matched = false;

            foreach ($parent['item'] as &$item) {
                if ($item['name'] === $segment) {
                    $parent = &$item;

                    if ($segment === $destination) {
                        $parent['item'][] = $request;
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

                $parent['item'][] = &$item;
                $parent = &$item;
            }

            unset($item);
        }
    }

    public function makeItem($route, $method, $routeHeaders)
    {
        return  [
            'name' => $route->uri(),
            'request' => [
                'method' => strtoupper($method),
                'header' => $routeHeaders,
                'url' => [
                    'raw' => '{{base_url}}/'.$route->uri(),
                    'host' => '{{base_url}}/'.$route->uri(),
                ],
            ],
        ];
    }
}
