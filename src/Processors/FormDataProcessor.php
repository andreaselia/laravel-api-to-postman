<?php

namespace AndreasElia\PostmanGenerator\Processors;

class FormDataProcessor
{
    public function process($reflectionMethod): array
    {
        $requestRules = [];

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

                $printRules = config('api-postman.print_rules');

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

        return $requestRules;
    }
}
