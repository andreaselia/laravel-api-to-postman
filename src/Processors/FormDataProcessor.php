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
                $rules->put($fieldName, $rule);

                if (is_array($rule) && in_array('confirmed', $rule)) {
                    $rules->put($fieldName.'_confirmation', $rule);
                } elseif (is_string($rule) && str_contains($rule, 'confirmed')) {
                    $rules->put($fieldName.'_confirmation', $rule);
                }
            }
        }

        return $rules;
    }
}
