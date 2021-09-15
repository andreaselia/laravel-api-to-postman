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

    /** @var string */
    private $bearer;

    public function __construct(Router $router, Repository $config)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = $config['api-postman'];
    }

    public function handle(): void
    {
        $this->setFilename();
        $this->setBearerToken();
        $this->initializeStructure();

        foreach ($this->router->getRoutes() as $route) {
            $methods = array_filter($route->methods(), fn ($value) => $value !== 'HEAD');
            $middlewares = $route->gatherMiddleware();

            foreach ($methods as $method) {
                $includedMiddleware = false;

                foreach ($middlewares as $middleware) {
                    if (in_array($middleware, $this->config['include_middleware'])) {
                        $includedMiddleware = true;
                    }
                }

                if (empty($middlewares) || ! $includedMiddleware) {
                    continue;
                }

                $requestRules = [];

                $routeAction = $route->getAction();

                $reflectionMethod = $this->getReflectionMethod($routeAction);

                if (! $reflectionMethod) {
                    continue;
                }

                if ($this->config['enable_formdata']) {
                    $rulesParameter = collect($reflectionMethod->getParameters())
                        ->filter(function ($value, $key) {
                            $value = $value->getType();

                            return $value && is_subclass_of($value->getName(), FormRequest::class);
                        })
                        ->first();

                    if ($rulesParameter) {
                        $rulesParameter = $rulesParameter->getType()->getName();
                        $rulesParameter = new $rulesParameter;
                        $rules = method_exists($rulesParameter, 'rules') ? $rulesParameter->rules() : [];

                        foreach ($rules as $fieldName => $rule) {
                            $requestRules[] = $fieldName;

                            if (is_string($rule)) {
                                $rule = preg_split('/\s*\|\s*/', $rule);
                            }

                            if (is_array($rule) && in_array('confirmed', $rule)) {
                                $requestRules[] = $fieldName.'_confirmation';
                            }
                        }
                    }
                }

                $routeHeaders = $this->config['headers'];

                if ($this->bearer && in_array($this->config['auth_middleware'], $middlewares)) {
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

        Storage::disk($this->config['disk'])->put($exportName = "postman/$this->filename", json_encode($this->structure));

        $this->info("Postman Collection Exported: $exportName");
    }

    protected function getReflectionMethod(array $routeAction): ?object
    {
        // Hydrates the closure if it is an instance of Opis\Closure\SerializableClosure
        if ($this->containsSerializedClosure($routeAction)) {
            $routeAction['uses'] = unserialize($routeAction['uses'])->getClosure();
        }

        if ($routeAction['uses'] instanceof Closure) {
            return new ReflectionFunction($routeAction['uses']);
        }

        $routeData = explode('@', $routeAction['uses']);
        $reflection = new ReflectionClass($routeData[0]);

        if (! $reflection->hasMethod($routeData[1])) {
            return null;
        }

        return $reflection->getMethod($routeData[1]);
    }

    public static function containsSerializedClosure(array $action): bool
    {
        return is_string($action['uses']) &&
            Str::startsWith($action['uses'], 'C:32:"Opis\\Closure\\SerializableClosure') !== false;
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

    protected function initializeStructure(): void
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

        if ($this->bearer) {
            $this->structure['variable'][] = [
                'key' => 'token',
                'value' => $this->bearer,
            ];
        }
    }

    protected function setFilename()
    {
        $this->filename = str_replace(
            ['{timestamp}', '{app}'],
            [date('Y_m_d_His'), Str::snake(config('app.name'))],
            $this->config['filename']
        );
    }

    protected function setBearerToken()
    {
        $this->bearer = $this->option('bearer') ?? null;
    }

    protected function isStructured()
    {
        return $this->config['structured'];
    }
}
