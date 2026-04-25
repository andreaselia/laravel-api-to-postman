<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Str;

class BodyModeResolver
{
    public function resolve(array $rules): string
    {
        $fileRules = ['file', 'image', 'mimes', 'upload', 'dimensions'];

        $hasNested = false;
        foreach ($rules as $fieldName => $rule) {
            if (str_contains($fieldName, '.') || str_contains($fieldName, '*')) {
                $hasNested = true;
            }

            if (is_string($rule)) {
                $ruleStr = $rule;
            } elseif (is_array($rule)) {
                $ruleStr = implode('|', array_filter(array_map(function ($r) {
                    return is_string($r) ? $r : (is_object($r) ? get_class($r) : '');
                }, $rule)));
            } else {
                $ruleStr = is_object($rule) ? get_class($rule) : '';
            }

            foreach ($fileRules as $fileRule) {
                if (Str::contains($ruleStr, $fileRule)) {
                    return 'formdata';
                }
            }
        }

        return $hasNested ? 'json' : 'default';
    }
}
