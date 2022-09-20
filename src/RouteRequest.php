<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationRuleParser;

class RouteRequest
{
    public function __construct($route, $method, $routeHeaders, $requestRules)
    {
        $printRules = config('api-postman.print_rules');

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
                $value = config('api-postman.formdata')[$rule['name']] ?? null;
                $description = $printRules
                    ? $this->parseRulesIntoHumanReadable($rule['name'], $rule['description'])
                    : '';

                $ruleData[] = [
                    'key' => $rule['name'],
                    'value' => $value,
                    'type' => 'text',
                    'description' => $description,
                ];
            }

            $data['request']['body'] = [
                'mode' => 'urlencoded',
                'urlencoded' => $ruleData,
            ];
        }

        return $data;
    }

    protected function parseRulesIntoHumanReadable($attribute, $rules): string
    {
        if (! config('api-postman.rules_to_human_readable')) {
            return is_array($rules)
                ? implode(', ', $rules)
                : $this->safelyStringifyClassBasedRule($rules);
        }

        if (is_object($rules)) {
            $rules = [$this->safelyStringifyClassBasedRule($rules)];
        }

        if (! is_array($rules)) {
            return '';
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
}
