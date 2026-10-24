<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NonStringableRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
    }
}
