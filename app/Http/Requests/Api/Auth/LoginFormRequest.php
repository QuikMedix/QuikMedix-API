<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember_me' => ['sometimes', 'boolean'],
            'os' => ['sometimes', 'integer'],
        ];
    }
}
