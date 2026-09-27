<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage-pharmacy', $this->route('pharmacy_id'));
    }

    /**
     * E-mail and phone are unique per pharmacy; users.email is varchar(191) and users.phone varchar(20).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $inThisPharmacy = fn (string $column) => Rule::unique('users', $column)->where('pharmacy_id', $this->route('pharmacy_id'));

        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:191', $inThisPharmacy('email')],
            'phone' => ['required', 'string', 'max:20', $inThisPharmacy('phone')],
            'home_phone' => ['nullable', 'string', 'max:20'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:25'],
            'apartment' => ['nullable', 'string', 'max:25'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A patient with this e-mail already exists in this pharmacy.',
            'phone.unique' => 'A patient with this phone number already exists in this pharmacy.',
        ];
    }
}
