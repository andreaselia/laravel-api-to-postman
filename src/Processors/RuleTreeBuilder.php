<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Arr;

class RuleTreeBuilder
{
    public function build(array $rules): array
    {
        $tree = [];

        foreach ($rules as $key => $rule) {
            Arr::set($tree, $key, $rule);
        }

        return $this->processTree($tree);
    }

    protected function processTree(array $tree): array
    {
        $result = [];

        foreach ($tree as $key => $value) {
            if ($key === '*') {
                return [$this->processValue($value)];
            }

            $result[$key] = $this->processValue($value);
        }

        return $result;
    }

    protected function processValue($value)
    {
        if (is_array($value)) {
            // Check if it's a numeric array (rules) or associative array (nested keys)
            if (Arr::isAssoc($value) || (isset($value[0]) && is_array($value[0])) || isset($value['*'])) {
                return $this->processTree($value);
            }
            return $value;
        }

        return $value;
    }
}
