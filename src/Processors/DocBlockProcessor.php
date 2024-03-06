<?php

namespace AndreasElia\PostmanGenerator\Processors;

use Illuminate\Support\Str;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

class DocBlockProcessor
{
    public function __invoke(ReflectionMethod|ReflectionFunction $reflectionMethod): string
    {
        try {
            $lexer = new Lexer;
            $constExprParser = new ConstExprParser;
            $parser = new PhpDocParser(new TypeParser($constExprParser), $constExprParser);

            $description = '';
            $comment = $reflectionMethod->getDocComment();
            $tokens = new TokenIterator($lexer->tokenize($comment));
            $phpDocNode = $parser->parse($tokens);

            foreach ($phpDocNode->children as $child) {
                if ($child instanceof PhpDocTextNode) {
                    $description .= ' '.$child->text;
                }
            }

            return Str::squish($description);
        } catch (Throwable $e) {
            return '';
        }
    }
}
