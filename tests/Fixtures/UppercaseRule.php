<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UppercaseRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * @return void
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
