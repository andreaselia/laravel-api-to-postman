<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class UppercaseRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (strtoupper($value) !== $value) {
            $fail("The {$attribute} must be uppercase.");
        }
    }

    public function __toString(): string
    {
        return 'uppercase';
    }
}
