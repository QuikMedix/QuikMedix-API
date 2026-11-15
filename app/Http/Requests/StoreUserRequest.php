<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The "add user" form in settings. The fields the form marks as required are required here too;
 * lengths follow the users table.
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:191'],
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'max:191'],
            'address' => ['required', 'string', 'max:100'],
            'zip' => ['required', 'string', 'max:30'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'pharmacy' => ['nullable', 'integer'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ];
    }
}
