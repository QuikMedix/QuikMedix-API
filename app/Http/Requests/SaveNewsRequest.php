<?php

namespace App\Http\Requests;

use App\Support\AccessMessages;
use Illuminate\Foundation\Http\FormRequest;

/**
 * The news form, for staff news and for a pharmacy's news to its patients. Both lists need a
 * title and a text; without them the item used to be dropped without a word.
 */
class SaveNewsRequest extends FormRequest
{
    /**
     * Patient news is written by a pharmacy's own staff; staff news is limited by its route.
     */
    public function authorize(): bool
    {
        return ! $this->is('news_patient/*') || ($this->user()->hasRole('medic') && ! empty($this->user()->pharmacy_id));
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
            'title' => ['required', 'string', 'max:200'],
            'text' => ['required', 'string'],
            'type' => ['nullable', 'string', 'max:50'],
            'link' => ['nullable', 'string'],
        ];
    }
}
