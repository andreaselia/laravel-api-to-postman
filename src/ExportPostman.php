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

        $routes = [
            'variable' => $variables,
            'info' => [
                'name' => $filename,
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
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
                    // TODO: structured, the "item" below can be replaced with a folder with "item" inside
                    $not = ['index', 'show', 'store', 'update', 'destroy'];

                    $routeName = $route->action['as'] ?? null;
                    $routeName = explode('.', $routeName);
                    $routeName = array_filter($routeName, fn ($value) => !is_null($value) && $value !== '' && !in_array($value, $not));

                    $folder = null;

                    // e.g. exploded version of "contests.submissions.index"
                    // the "index" route would go inside these 2 nested
                    // folders in order to be structured.

                    if ($routeName) {
                        $routes['item'][] = [
                            'name' => 'contests',
                            'item' => [
                                [
                                    'name' => 'submissions',
                                    'item' => [],
                                ],
                            ],
                        ];
                    }

                    foreach ($routeName as $name) {
                        // dd($name);
                    }

                    // print_r($routes);

                    $routes['item'][] = [
                        'name' => $method . ' | ' . $route->uri(),
                        'request' => [
                            'method' => strtoupper($method),
                            'header' => $routeHeaders,
                            'url' => [
                                'raw' => '{{base_url}}/' . $route->uri(),
                                'host' => '{{base_url}}/' . $route->uri(),
                            ],
                        ],
                    ];
                } else {
                    $routes['item'][] = [
                        'name' => $method . ' | ' . $route->uri(),
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
        }

        Storage::put($exportName = "$filename.json", json_encode($routes));

        $this->info("Postman Collection Exported: $exportName");
    }
}
