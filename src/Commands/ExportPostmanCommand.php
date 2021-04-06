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
                    $rulesParameter = null;

                    foreach ($reflectionMethod->getParameters() as $parameter) {
                        if (! $parameterType = $parameter->getType()) {
                            continue;
                        }

                        $requestClass = $parameterType->getName();

                        if (class_exists($requestClass)) {
                            $rulesParameter = new $requestClass();
                        }
                    }

                    if ($rulesParameter && $rulesParameter instanceof FormRequest) {
                        $requestRules = $rulesParameter->rules();

                        $requestRules = array_keys($requestRules);
                    } else {
                        $requestRules = $this->parseForInlineRules($reflectionMethod);
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

    /**
     * Look for inline validations such as $request->make() and
     * Validator::make().
     * 
     * @param object $reflectionMethod
     * @return array
     */
    protected function parseForInlineRules($reflectionMethod)
    {
        $controllerPath = $reflectionMethod->getFileName();
        $controllerContents = file_get_contents($controllerPath);

        $tokens = token_get_all($controllerContents);
        
        $fields = [];

        $inMethod = '';
        $inValidation = false;
        $parsedFields = false;
        foreach ($tokens as $k => $token) {
            if (is_array($token)) {
                if(token_name($token[0]) === 'T_FUNCTION') $inMethod = $tokens[$k + 2][1];
                if($inMethod === $reflectionMethod->name) {
                    if(token_name($token[0]) === 'T_STRING' && 
                        ($token[1] === 'validate' && $tokens[$k-1][1] === '->') || // Matching $request->make
                        ($token[1] === 'make' && $tokens[$k-2][1] === 'Validator')) { // Matching Validator::make
                        $inValidation = true;
                        continue;
                    }

                    if(in_array(token_name($token[0]), ['T_STRING', 'T_VARIABLE', 'T_FUNCTION']) && $parsedFields) { // When we're outside the validator
                        $inValidation = false;
                        continue;
                    }

                    if($inValidation) {
                        if(token_name($token[0]) === 'T_DOUBLE_ARROW') {
                            $field = $tokens[$k-1];
                            if(token_name($tokens[$k-1][0]) == 'T_WHITESPACE') $field = $tokens[$k-2];

                            $fields[] = str_replace(['"', "'"], "", $field[1]);
                            $parsedFields = true;
                        }
                    }
                }
            }
        }
        
        return $fields;
    }

    protected function getReflectionMethod(array $routeAction): ?object
    {
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
