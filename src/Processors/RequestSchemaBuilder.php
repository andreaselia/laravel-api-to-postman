<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Collection;

class RequestSchemaBuilder
{
    private RuleTreeBuilder $treeBuilder;
    private ValueResolver $valueResolver;

    public function __construct()
    {
        $this->treeBuilder = new RuleTreeBuilder();
        $this->valueResolver = new ValueResolver();
    }

    public function build(array $rules): array
    {
        $tree = $this->treeBuilder->build($rules);

        return $this->resolveValues($tree);
    }

    protected function resolveValues(array $tree): array
    {
        $result = [];

        foreach ($tree as $key => $value) {
            if (is_array($value)) {
                if (isset($value[0]) && is_array($value[0])) {
                    // It's an array of objects
                    $result[$key] = [$this->resolveValues($value[0])];
                } elseif (isset($value[0])) {
                    // It's an array of values (rules)
                    $result[$key] = $this->valueResolver->resolve($value);
                } else {
                    // It's a nested object
                    $result[$key] = $this->resolveValues($value);
                }
            } else {
                $result[$key] = $this->valueResolver->resolve((array) $value);
            }
        }

        return $result;
    }
}
