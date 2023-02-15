<?php

namespace AndreasElia\PostmanGenerator\Commands;

use Closure;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionClass;
use ReflectionFunction;

class ExportPostmanCommand extends Command
{
    /** @var string */
    protected $signature = 'export:postman
                            {--bearer= : The bearer token to use on your endpoints}
                            {--basic= : The basic auth to use on your endpoints}';

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
    private $token;

    /** @var string */
    private $authType;

    /** @var array */
    private const AUTH_OPTIONS = [
        'bearer',
        'basic',
    ];

    /** @var \Illuminate\Validation\Validator */
    private $validator;

    public function __construct(Router $router, Repository $config)
    {
        parent::__construct();

        $this->router = $router;
        $this->config = $config['api-postman'];
    }

    public function handle(): void
    {
        $this->setFilename();
        $this->setAuthToken();
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
                            if (is_string($rule)) {
                                $rule = preg_split('/\s*\|\s*/', $rule);
                            }

                            $printRules = $this->config['print_rules'];

                            $requestRules[] = [
                                'name' => $fieldName,
                                'description' => $printRules ? $rule : '',
                            ];

                            if (is_array($rule) && in_array('confirmed', $rule)) {
                                $requestRules[] = [
                                    'name' => $fieldName.'_confirmation',
                                    'description' => $printRules ? $rule : '',
                                ];
                            }
                        }
                    }
                }

                $routeHeaders = $this->config['headers'];

                if ($this->token && in_array($this->config['auth_middleware'], $middlewares)) {
                    switch ($this->authType) {
                        case 'bearer':
                            $routeHeaders[] = [
                                'key' => 'Authorization',
                                'value' => 'Bearer {{token}}',
                            ];
                            break;

                        case 'basic':
                            $routeHeaders[] = [
                                'key' => 'Authorization',
                                'value' => 'Basic {{token}}',
                            ];
                            break;
                    }
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

                    if (! $this->createCrudFolders()) {
                        if (in_array(end($routeNames), ['index', 'store', 'show', 'update', 'destroy'])) {
                            unset($routeNames[array_key_last($routeNames)]);
                        }

                        if ($routeNames[0] == 'api') {
                            unset($routeNames[0]);
                        }
                    }

                    $this->buildTree($this->structure, $routeNames, $request);
                } else {
                    $this->structure['item'][] = $request;
                }
            }
        }

        Storage::disk($this->config['disk'])->put($exportName = "postman/$this->filename", json_encode($this->structure));

        $this->info('Postman Collection Exported: '.storage_path('app/'.$exportName));
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
        return is_string($action['uses']) && Str::startsWith($action['uses'], [
            'C:32:"Opis\\Closure\\SerializableClosure',
            'O:47:"Laravel\SerializableClosure\\SerializableClosure',
            'O:55:"Laravel\\SerializableClosure\\UnsignedSerializableClosure',
        ]);
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
        $printRules = $this->config['print_rules'];

        $uri = Str::of($route->uri())->replaceMatches('/{([[:alnum:]]+)}/', ':$1');

        $variables = $uri->matchAll('/(?<={)[[:alnum:]]+(?=})/m');

        $data = [
            'name' => $route->uri(),
            'request' => [
                'method' => strtoupper($method),
                'header' => $routeHeaders,
                'url' => [
                    'raw' => '{{base_url}}/'.$uri,
                    'host' => ['{{base_url}}'],
                    'path' => $uri->explode('/')->filter(),
                    'variable' => $variables->transform(function ($variable) {
                        return ['key' => $variable, 'value' => ''];
                    })->all(),
                ],
            ],
        ];

        if ($requestRules) {
            $ruleData = [];

            foreach ($requestRules as $rule) {
                $ruleData[] = [
                    'key' => $rule['name'],
                    'value' => $this->config['formdata'][$rule['name']] ?? null,
                    'type' => 'text',
                    'description' => $printRules ? $this->parseRulesIntoHumanReadable($rule['name'], $rule['description']) : '',
                ];
            }

            $data['request']['body'] = [
                'mode' => 'urlencoded',
                'urlencoded' => $ruleData,
            ];
        }

        return $data;
    }

    /**
     * Process a rule set and utilize the Validator to create human readable descriptions
     * to help users provide valid data.
     *
     * @param $attribute
     * @param $rules
     * @return string
     */
    protected function parseRulesIntoHumanReadable($attribute, $rules): string
    {
        // ... bail if user has asked for non interpreted strings:
        if (! $this->config['rules_to_human_readable']) {
            foreach ($rules as $i => $rule) {
                // because we don't support custom rule classes, we remove them from the rules
                if (is_subclass_of($rule, Rule::class)) {
                    unset($rules[$i]);
                }
            }

            return is_array($rules) ? implode(', ', $rules) : $this->safelyStringifyClassBasedRule($rules);
        }

        /*
         * An object based rule is presumably a Laravel default class based rule or one that implements the Illuminate
         * Rule interface. Lets try to safely access the string representation...
         */
        if (is_object($rules)) {
            $rules = [$this->safelyStringifyClassBasedRule($rules)];
        }

        /*
         * Handle string based rules (e.g. required|string|max:30)
         */
        if (is_array($rules)) {
            foreach ($rules as $i => $rule) {
                if (is_object($rule)) {
                    unset($rules[$i]);
                }
            }

            $this->validator = Validator::make([], [$attribute => implode('|', $rules)]);

            foreach ($rules as $rule) {
                [$rule, $parameters] = ValidationRuleParser::parse($rule);

                $this->validator->addFailure($attribute, $rule, $parameters);
            }

            $messages = $this->validator->getMessageBag()->toArray()[$attribute];

            if (is_array($messages)) {
                $messages = $this->handleEdgeCases($messages);
            }

            return implode(', ', is_array($messages) ? $messages : $messages->toArray());
        }

        // ...safely return a safe value if we encounter neither a string or object based rule set:
        return '';
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
            'event' => [],
        ];

        $prerequestPath = $this->config['prerequest_script'];
        $testPath = $this->config['test_script'];

        if ($prerequestPath || $testPath) {
            $scripts = [
                'prerequest' => $prerequestPath,
                'test' => $testPath,
            ];

            foreach ($scripts as $type => $path) {
                if (file_exists($path)) {
                    $this->structure['event'][] = [
                        'listen' => $type,
                        'script' => [
                            'type' => 'text/javascript',
                            'exec' => file_get_contents($path),
                        ],
                    ];
                }
            }
        }

        if ($this->token) {
            $this->structure['variable'][] = [
                'key' => 'token',
                'value' => $this->token,
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

    protected function setAuthToken()
    {
        foreach (self::AUTH_OPTIONS as $option) {
            if ($token = $this->option($option)) {
                $this->token = $token ?? null;
                $this->authType = $option;
            }
        }
    }

    protected function isStructured()
    {
        return $this->config['structured'];
    }

    protected function createCrudFolders()
    {
        return $this->config['crud_folders'];
    }

    /**
     * Certain fields are not handled via the normal throw failure method in the validator
     * We need to add a human readable message.
     *
     * @param  array  $messages
     * @return array
     */
    protected function handleEdgeCases(array $messages): array
    {
        foreach ($messages as $key => $message) {
            if ($message === 'validation.nullable') {
                $messages[$key] = '(Nullable)';
                continue;
            }

            if ($message === 'validation.sometimes') {
                $messages[$key] = '(Optional)';
            }
        }

        return $messages;
    }

    /**
     * In this case we have received what is most likely a Rule Object but are not certain.
     *
     * @param $probableRule
     * @return string
     */
    protected function safelyStringifyClassBasedRule($probableRule): string
    {
        if (! is_object($probableRule) || is_subclass_of($probableRule, Rule::class) || ! method_exists($probableRule, '__toString')) {
            return '';
        }

        return (string) $probableRule;
    }
}
