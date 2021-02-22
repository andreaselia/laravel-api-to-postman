<?php

namespace AndreasElia\PostmanGenerator\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;

class ExportPostmanCommand extends Command
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

    /** @var null */
    protected $filename;

    public function __construct(Router $router, Repository $config)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = $config['api-postman'];
        $this->filename = $this->formatFilename();
    }

    public function handle(): void
    {
        $this->initStructure();

        if ($bearer = $this->option('bearer') ?? false) {
            $this->structure['variable'][] = [
                'key' => 'token',
                'value' => $bearer,
            ];
        }

        foreach ($this->router->getRoutes() as $route) {
            $methods = collect($route->methods())->reject(fn ($method) => $method == 'HEAD');
            $middleware = $route->gatherMiddleware();

            foreach ($methods as $method) {
                $includedMiddleware = false;

                foreach ($middleware as $mw) {
                    if (! in_array($mw, $this->config['include_middleware'])) {
                        continue;
                    }

                    $includedMiddleware = true;
                }

                if (empty($middleware) && ! $includedMiddleware) {
                    continue;
                }

                $requestRules = [];

                if ($this->config['enable_formdata']) {
                    $routeAction = $route->getAction();

                    if ($routeAction['uses'] instanceof Closure) {
                        $reflectionMethod = new ReflectionFunction($routeAction['uses']);
                    } else {
                        $routeData = explode('@', $routeAction['uses']);
                        $reflection = new ReflectionClass($routeData[0]);
                        $reflectionMethod = $reflection->getMethod($routeData[1]);
                    }

                    $firstParameter = $reflectionMethod->getParameters()[0] ?? false;

                    if ($firstParameter) {
                        $requestClass = $firstParameter->getType()->getName();
                        $requestClass = class_exists($requestClass) ? new $requestClass() : null;

                        if ($requestClass instanceof FormRequest) {
                            $requestRules = $requestClass->rules();

                            $requestRules = array_keys($requestRules);
                        }
                    }
                }

                $routeHeaders = $this->config['headers'];

                if ($bearer && in_array($this->config['auth_middleware'], $middleware)) {
                    $routeHeaders[] = [
                        'key' => 'Authorization',
                        'value' => 'Bearer {{token}}',
                    ];
                }

                $request = $this->makeRequest($route, $method, $routeHeaders, $requestRules);

                if ($this->isStructured()) {
                    $routeNames = $route->action['as'] ?? null;

                    if (! $routeNames) {
                        $routeUri = explode('/', $route->uri());

                        // remove "api" from the start
                        unset($routeUri[0]);

                        $routeNames = implode('.', $routeUri);
                    }

                    $routeNames = explode('.', $routeNames);
                    $routeNames = array_filter($routeNames, function ($value) {
                        return ! is_null($value) && $value !== '';
                    });

                    $this->buildTree($this->structure, $routeNames, $request);
                } else {
                    $this->structure['item'][] = $request;
                }
            }
        }

        Storage::put($exportName = "postman/$this->filename", json_encode($this->structure));

        $this->info("Postman Collection Exported: $exportName");
    }

    protected function buildTree(array &$routes, array $segments, array $request): void
    {
        $parent = &$routes;
        $destination = end($segments);

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
                    'item' => $segment === $destination ? [$request] : [],
                ];

                $parent['item'][] = &$item;
                $parent = &$item;
            }

            unset($item);
        }
    }

    public function makeRequest($route, $method, $routeHeaders, $requestRules)
    {
        $data = [
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

        if ($requestRules) {
            $ruleData = [];

            foreach ($requestRules as $rule) {
                $ruleData[] = [
                    'key' => $rule,
                    'value' => $this->config['formdata'][$rule] ?? null,
                    'type' => 'text',
                ];
            }

            $data['request']['body'] = [
                'mode' => 'urlencoded',
                'urlencoded' => $ruleData,
            ];
        }

        return $data;
    }

    protected function initStructure(): void
    {
        $this->structure = [
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
        ];
    }

    protected function formatFilename()
    {
        return str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            $this->config['filename']
        );
    }

    protected function isStructured()
    {
        return $this->config['structured'];
    }
}
