<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PharmacyDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $driverId = $this->route('user_id');

        return $driverId === null
            ? $this->user()->can('manage-pharmacy', $this->route('pharmacy_id'))
            : $this->user()->can('manage-pharmacy-driver', [$this->route('pharmacy_id'), $driverId]);
    }

    /**
     * Column limits: users.email is varchar(191) and users.phone varchar(20).
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $driverId = $this->route('user_id');
        $image = ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users', 'email')->ignore($driverId)],
            'phone' => ['required', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($driverId)],
            'password' => $driverId === null ? ['required', 'string', 'min:8'] : ['prohibited'],
            'address' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:25'],
            'apartment' => ['nullable', 'string', 'max:25'],
            'driving_license' => ['required', 'string', 'max:255'],
            'identification_cards' => ['required', 'string', 'max:255'],
            'car_info' => ['required', 'string', 'max:255'],
            'payment_card' => ['required', 'string', 'max:255'],
            'transport' => ['nullable', 'string', 'max:255'],
            'image' => $image,
            'driving_license_img' => $image,
            'car_img' => $image,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'A user with this e-mail already exists.',
            'phone.unique' => 'A user with this phone number already exists.',
            'mimes' => 'The :attribute must be a JPG or PNG image.',
            'max.file' => 'The :attribute must be smaller than 6 MB.',
        ];
    }
}
