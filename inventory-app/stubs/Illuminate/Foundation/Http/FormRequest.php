<?php

namespace Illuminate\Foundation\Http;

class FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function route(?string $parameter = null): mixed
    {
        return null;
    }
}
