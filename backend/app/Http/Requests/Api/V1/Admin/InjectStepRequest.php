<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;

class InjectStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'resolver_type' => ['required', 'string', 'max:64'],
            'config' => ['sometimes', 'array'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
