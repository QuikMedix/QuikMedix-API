<?php

namespace App\Http\Requests;

class StoreFacilityRequest extends StorePatientRequest
{
    /**
     * Same as a patient, except a facility may have no last name.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return ['last_name' => ['nullable', 'string', 'max:255']] + parent::rules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A facility with this e-mail already exists in this pharmacy.',
            'phone.unique' => 'A facility with this phone number already exists in this pharmacy.',
        ];
    }
}
