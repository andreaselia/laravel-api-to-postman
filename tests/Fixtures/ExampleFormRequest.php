<?php

namespace AndreasElia\PostmanGenerator\Tests\Fixtures;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\In;

class ExampleFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'field_1' => 'required',
            'field_2' => 'required|integer',
            'field_3' => 'sometimes|integer',
            'field_4' => 'nullable|integer',
            'field_5' => 'required|integer|max:30|min:1',
            'field_6' => new In([1, 2, 3]),
            'field_7' => ['required', new In([1, 2, 3])],
            'field_8' => new UppercaseRule,
            'field_9' => ['required', 'string', new UppercaseRule],
        ];
    }
}
