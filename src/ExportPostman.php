<?php

namespace AndreasElia\PostmanGenerator;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportPostman extends Command
{
    /** @var string */
    protected $signature = 'export:postman {--structured} {--bearer}';

    /** @var string */
    protected $description = 'Automatically generate a Postman collection for your API routes';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $structured = $this->option('structured') ?? false;
        $bearer = $this->option('bearer') ?? false;

        $routerRoutes = app('router')->getRoutes();

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

        $routeHeaders = [
            [
                'key' => 'Content-Type',
                'value' => 'application/json',
            ],
        ];

        if ($bearer) {
            $routeHeaders[] = [
                'key' => 'Authorization',
                'value' => 'Bearer {{token}}',
            ];
        }

        foreach ($routerRoutes as $route) {
            foreach ($route->methods as $method) {
                if ($method == 'HEAD' || empty($route->middleware()) || $route->middleware()[0] !== 'api') {
                    continue;
                }

                // TODO: structured, the "item" below can be replaced with a folder with "item" inside

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

        Storage::put($exportName = "$filename.json", json_encode($routes));

        $this->info("Postman Collection Exported: $exportName");
    }
}
