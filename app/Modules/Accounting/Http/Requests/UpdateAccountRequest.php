<?php

namespace App\Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Only name and description are editable.
        // type, code, normal_balance, parent_id are immutable after creation.
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'اسم الحساب مطلوب.',
        ];
    }
}
