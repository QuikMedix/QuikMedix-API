<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The office form, for adding and editing. Lengths follow the offices table.
 */
class SaveOfficeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:50'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:200'],
            'zone_id' => ['required', 'integer'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ];
    }
}
