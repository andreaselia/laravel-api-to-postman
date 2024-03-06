<?php

namespace AndreasElia\PostmanGenerator\Processors;

use AndreasElia\PostmanGenerator\Concerns\HasAuthentication;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Validation\ValidationRuleParser;
use ReflectionClass;
use ReflectionFunction;

class RouteProcessor
{
    use HasAuthentication;

    private array $config;

    private Router $router;

    private array $output;

    public function __construct(Repository $config, Router $router)
    {
        $this->config = $config['api-postman'];

        $this->router = $router;

        $this->resolveAuth();
    }

    public function process(array $output): array
    {
        $this->output = $output;

        $routes = collect($this->router->getRoutes());

        /** @var Route $route */
        foreach ($routes as $route) {
            $this->processRoute($route);
        }

        return $this->output;
    }

    /**
     * @throws \ReflectionException
     */
    protected function processRoute(Route $route)
    {
        try {
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

                $reflectionMethod = $this->getReflectionMethod($route->getAction());

                if (! $reflectionMethod) {
                    continue;
                }

                $routeHeaders = $this->config['headers'];

                if ($this->authentication && in_array($this->config['auth_middleware'], $middlewares)) {
                    $routeHeaders[] = $this->authentication->toArray();
                }

                $uri = Str::of($route->uri())
                    ->after('/')
                    ->replaceMatches('/{([[:alnum:]]+)}/', ':$1');

                //            if (!$uri->toString()) {
                //                return [];
                //            }

                if ($this->config['include_doc_comments']) {
                    $description = (new DocBlockProcessor)($reflectionMethod);
                }

                $data = [
                    'name' => $route->uri(),
                    'request' => array_merge(
                        $this->processRequest(
                            $method,
                            $uri,
                            $this->config['enable_formdata'] ? (new FormDataProcessor)->process($reflectionMethod) : collect()
                        ),
                        ['description' => $description ?? '']
                    ),
                    'response' => [],

                    'protocolProfileBehavior' => [
                        'disableBodyPruning' => $this->config['protocol_profile_behavior']['disable_body_pruning'] ?? false,
                    ],
                ];

                if ($this->config['structured']) {
                    $routeNameSegments = (
                        $route->getName()
                            ? Str::of($route->getName())->explode('.')
                            : Str::of($route->uri())->after('api/')->explode('/')
                    )->filter(fn ($value) => ! is_null($value) && $value !== '');

                    if (! $this->config['crud_folders']) {
                        if (in_array($routeNameSegments->last(), ['index', 'store', 'show', 'update', 'destroy'])) {
                            $routeNameSegments->forget($routeNameSegments->count() - 1);
                        }
                    }

                    $this->buildTree($this->output, $routeNameSegments->all(), $data);
                } else {
                    $this->output['item'][] = $data;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to process route: '.$route->uri());
        }
    }

    protected function processRequest(string $method, Stringable $uri, Collection $rules): array
    {
        return collect([
            'method' => strtoupper($method),
            'header' => collect($this->config['headers'])
                ->push($this->authentication?->toArray())
                ->all(),
            'url' => [
                'raw' => '{{base_url}}/'.$uri,
                'host' => ['{{base_url}}'],
                'path' => $uri->explode('/')->filter()->all(),
                'variable' => $uri
                    ->matchAll('/(?<={)[[:alnum:]]+(?=})/m')
                    ->transform(function ($variable) {
                        return ['key' => $variable, 'value' => ''];
                    })
                    ->all(),
            ],
        ])
            ->when($rules, function (Collection $collection, Collection $rules) use ($method) {
                if ($rules->isEmpty()) {
                    return $collection;
                }

                $rules->transform(fn ($rule) => [
                    'key' => $rule['name'],
                    'value' => $this->config['formdata'][$rule['name']] ?? null,
                    'description' => $this->config['print_rules'] ? $this->parseRulesIntoHumanReadable($rule['name'], $rule['description']) : null,
                ]);

                if ($method === 'GET') {
                    return $collection->put('url', [
                        'query' => $rules->map(fn ($value) => array_merge($value, ['disabled' => false])),
                    ]);
                }

                return $collection->put('body', [
                    'mode' => 'urlencoded',
                    'urlencoded' => $rules->map(fn ($value) => array_merge($value, ['type' => 'text'])),
                ]);
            })
            ->all();
    }

    protected function processResponse(string $method, array $action): array
    {
        return [
            'code' => 200,
            'body' => [
                'mode' => 'raw',
                'raw' => '',
            ],
        ];
    }

    /**
     * @throws \ReflectionException
     */
    private function getReflectionMethod(array $routeAction): ?object
    {
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

    private function containsSerializedClosure(array $action): bool
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

            $validator = Validator::make([], [$attribute => implode('|', $rules)]);

            foreach ($rules as $rule) {
                [$rule, $parameters] = ValidationRuleParser::parse($rule);

                $validator->addFailure($attribute, $rule, $parameters);
            }

            $messages = $validator->getMessageBag()->toArray()[$attribute];

            if (is_array($messages)) {
                $messages = $this->handleEdgeCases($messages);
            }

            return implode(', ', is_array($messages) ? $messages : $messages->toArray());
        }

        // ...safely return a safe value if we encounter neither a string or object based rule set:
        return '';
    }

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
     */
    protected function safelyStringifyClassBasedRule($probableRule): string
    {
        if (! is_object($probableRule) || is_subclass_of($probableRule, Rule::class) || ! method_exists($probableRule, '__toString')) {
            return '';
        }

        return (string) $probableRule;
    }
}
