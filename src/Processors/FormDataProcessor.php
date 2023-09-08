<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use ReflectionParameter;

class FormDataProcessor
{
    public function process($reflectionMethod): Collection
    {
        $rules = collect();

        /** @var ReflectionParameter $rulesParameter */
        $rulesParameter = collect($reflectionMethod->getParameters())
            ->first(function ($value) {
                $value = $value->getType();

                return $value && is_subclass_of($value->getName(), FormRequest::class);
            });

        if ($rulesParameter) {
            /** @var FormRequest $class */
            $class = new ($rulesParameter->getType()->getName());

            $classRules = method_exists($class, 'rules') ? $class->rules() : [];

            foreach ($classRules as $fieldName => $rule) {
                if (is_string($rule)) {
                    $rule = preg_split('/\s*\|\s*/', $rule);
                }

                $printRules = config('api-postman.print_rules');

                $rules->push([
                    'name' => $fieldName,
                    'description' => $printRules ? $rule : '',
                ]);

                if (is_array($rule) && in_array('confirmed', $rule)) {
                    $rules->push([
                        'name' => $fieldName.'_confirmation',
                        'description' => $printRules ? $rule : '',
                    ]);
                }
            }
        }

        return $rules;
    }
}
