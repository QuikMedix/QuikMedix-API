<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

/**
 * The "edit user" form in settings. Address and ZIP are only on the form for some roles, so
 * they are checked when sent; a blank password means "keep the current one".
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * The same two checks the handler makes, made here so that they come before validation:
     * the right to manage this account, and the right to hand out the role being set.
     */
    public function authorize(): bool
    {
        $userId = $this->route('user_id');
        if (! $this->user()->can('manage-account', [$userId])) {
            return false;
        }
        $role = $this->input('role');

        return ! $this->filled('role')
            || $role === DB::table('users')->where('id', $userId)->value('role')
            || $this->user()->can('assign-role', $role);
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
            'password' => ['nullable', 'string', 'min:8', 'max:191'],
            'address' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:30'],
            'apartment' => ['nullable', 'string', 'max:50'],
            'image' => ['nullable', 'file', 'mimes:jpeg,jpg,png', 'max:6000'],
        ];
    }
}
