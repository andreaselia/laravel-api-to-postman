<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Str;
use ReflectionFunction;
use ReflectionMethod;

class DocBlockProcessor
{
    public function __invoke(ReflectionMethod|ReflectionFunction $reflectionMethod): string
    {
        $comment = $reflectionMethod->getDocComment();

        if (! $comment) {
            return '';
        }

        $description = collect(preg_split('/\R/', $comment) ?: [])
            ->map(function (string $line) {
                $line = trim($line);

                if (in_array($line, ['/**', '/*', '*/', '*'], true)) {
                    return '';
                }

                if (Str::startsWith($line, ['/**', '/*'])) {
                    $line = ltrim(substr($line, 3));
                } elseif (Str::startsWith($line, '*')) {
                    $line = ltrim(substr($line, 1));
                }

                if (Str::endsWith($line, '*/')) {
                    $line = rtrim(substr($line, 0, -2));
                }

                return $line;
            })
            ->reject(fn (string $line) => $line === '')
            ->reject(fn (string $line) => Str::startsWith($line, '@'))
            ->implode(' ');

        return Str::squish($description);
    }
}
