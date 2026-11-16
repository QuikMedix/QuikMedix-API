<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The tariff plan form, for adding and editing. Every price is required and cannot be negative,
 * as the form's own fields say; the order rate is a whole number of at least one.
 */
class SavePlanRequest extends FormRequest
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
        $price = ['required', 'numeric', 'min:0'];

        return [
            'name' => ['required', 'string', 'max:50'],
            'order_rate' => ['required', 'integer', 'min:1'],
            'tariff' => $price,
            'tariff_next_day' => $price,
            'tariff_same_day' => $price,
            'tariff_asap' => $price,
            'tariff_after_hours' => $price,
            'tariff_fridge' => $price,
            'tariff_area2' => $price,
            'tariff_area3' => $price,
            'tariff_area_more' => $price,
        ];
    }
}
