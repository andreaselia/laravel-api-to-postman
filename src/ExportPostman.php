<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Routing\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportPostman extends Command
{
    /** @var string */
    protected $signature = 'export:postman {--structured} {--bearer}';

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
        $bearer = $this->option('bearer') ?? false;

        $filename = date('Y_m_d_His') . '_postman';

        $variables = [
            [
                'key' => 'base_url',
                'value' => 'https://api.example.com/',
            ],
        ];

        if ($bearer) {
            $variables[] = [
                'key' => 'token',
                'value' => '1|token',
            ];
        }

        $this->routes = [
            'variable' => $variables,
            'info' => [
                'name' => $filename,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $routerRoutes = $this->router->getRoutes();

        foreach ($routerRoutes as $route) {
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

                if ($structured) {
                    $not = ['index', 'show', 'store', 'update', 'destroy'];

                    $routeNames = $route->action['as'] ?? null;
                    $routeNames = explode('.', $routeNames);
                    $routeNames = array_filter($routeNames, function ($value) use ($not) {
                        return !is_null($value) && $value !== '' && !in_array($value, $not);
                    });

                    $destination = end($routeNames);

                    $this->nestedRoutes($routeNames, $route, $method, $routeHeaders, $destination);
                }
            }
        }

        Storage::put($exportName = "$filename.json", json_encode($this->routes));

        $this->info("Postman Collection Exported: $exportName");
    }

    public function nestedRoutes(&$routeNames, $route, $method, $routeHeaders, $destination)
    {
        $item = $this->makeItem($route, $method, $routeHeaders);

        if (empty($routeNames)) {
            return $item;
        }

        $index = array_shift($routeNames);

        if ($this->findKeyValue($this->routes, 'name', $index, $item, $destination)) {
            return;
        }

        $this->routes['item'][] = [
            'name' => $index,
            'item' => [$this->nestedRoutes($routeNames, $route, $method, $routeHeaders, $destination)],
        ];
    }

    public function findKeyValue(&$array, $key, $val, $template, $destination)
    {
        foreach ($array as &$item) {
            if (is_array($item) && $this->findKeyValue($item, $key, $val, $template, $destination)) {
                return true;
            }

            if (isset($item[$key]) && $item[$key] == $val) {
                $item['item'][] = $template;

                return true;
            }
        }

        return false;
    }

    public function makeItem($route, $method, $routeHeaders)
    {
        return  [
            'name' => $route->uri(),
            'request' => [
                'method' => strtoupper($method),
                'header' => $routeHeaders,
                'url' => [
                    'raw' => '{{base_url}}/' . $route->uri(),
                    'host' => '{{base_url}}/' . $route->uri(),
                ],
            ],
        ];
    }
}
