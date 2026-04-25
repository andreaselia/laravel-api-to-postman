<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Str;

class ValueResolver
{
    public function resolve(array $rules): mixed
    {
        $rules = collect($rules)->map(fn ($rule) => is_string($rule) ? strtolower($rule) : $rule);

        if ($rules->contains('email')) {
            return 'user@example.com';
        }

        if ($rules->contains('integer')) {
            return 1;
        }

        if ($rules->contains('numeric')) {
            return 0;
        }

        if ($rules->contains('boolean')) {
            return true;
        }

        if ($rules->contains('date')) {
            return '2026-01-01';
        }

        if ($rules->contains('array')) {
            return [];
        }

        if ($rules->contains('string')) {
            return 'string';
        }

        return '';
    }
}
