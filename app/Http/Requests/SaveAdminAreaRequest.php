<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The admin area form, for adding and editing: a name and the delivery areas it covers.
 */
class SaveAdminAreaRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:50'],
            'office_id' => ['nullable', 'integer'],
            'tariff_areas' => ['nullable', 'array'],
            'tariff_areas.*' => ['integer'],
        ];
    }
}
