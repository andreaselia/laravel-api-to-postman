<?php

namespace AndreasElia\PostmanGenerator\Processors;

class RouteProcessor
{
    public function process(array $routes): array
    {
        $collection = [];

        foreach ($routes as $route) {
            $collection[] = $this->processRoute($route);
        }

        return $collection;
    }

    protected function processRoute(array $route): array
    {
        return [
            'name' => $route['name'],
            'request' => $this->processRequest($route['method'], $route['uri']),
            'response' => $this->processResponse($route['method'], $route['action']),
        ];
    }

    protected function processRequest(string $method, string $uri): array
    {
        return [
            'method' => $method,
            'url' => $uri,
        ];
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
}
