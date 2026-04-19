<?php

namespace App\Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'description'    => ['required', 'string'],
            'entry_date'     => ['required', 'date'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id'   => ['nullable', 'integer'],

            'lines'                  => ['required', 'array', 'min:2'],
            'lines.*.account_id'     => ['required', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', $companyId)],
            'lines.*.debit'          => ['required', 'numeric', 'min:0'],
            'lines.*.credit'         => ['required', 'numeric', 'min:0'],
            'lines.*.description'    => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'lines.min'                  => 'A journal entry requires at least 2 lines.',
            'lines.*.account_id.exists'  => 'One or more accounts do not belong to your company.',
        ];
    }
}
