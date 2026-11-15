<?php

namespace App\Http\Requests;

use App\Support\AccessMessages;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The pharmacy form, for adding and editing. Zone and plan are on the form only for
 * administrators, so they are required when sent. Lengths follow the pharmacys table.
 */
class SavePharmacyRequest extends FormRequest
{
    /**
     * Administrators add and edit pharmacies; a person with the plain user role may register one.
     */
    public function authorize(): bool
    {
        return $this->user()->can('admin') || ($this->route('pharmacy_id') === null && $this->user()->hasRole('user'));
    }

    protected function failedAuthorization(): never
    {
        abort(403, AccessMessages::NO_PERMISSION);
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
            'site' => ['required', 'string', 'max:200'],
            'zone_id' => ['sometimes', 'required', 'integer'],
            'plan_id' => ['sometimes', 'required', 'integer'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ];
    }
}
