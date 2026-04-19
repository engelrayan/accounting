<?php

namespace App\Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $hasParent = $this->filled('parent_id');

        // In child mode (parent_id given): only name is required — code/type/normal_balance
        // are auto-derived from the parent in the controller.
        return [
            'parent_id'      => ['nullable', 'integer', Rule::exists('accounts', 'id')->where('tenant_id', $companyId)],
            'code'           => $hasParent
                ? ['nullable']
                : ['required', 'string', 'max:20', Rule::unique('accounts')->where('tenant_id', $companyId)],
            'name'           => ['required', 'string', 'max:100'],
            'type'           => $hasParent
                ? ['nullable']
                : ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
            'normal_balance' => $hasParent
                ? ['nullable']
                : ['required', Rule::in(['debit', 'credit'])],
            'description'    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'           => 'اسم الحساب مطلوب.',
            'code.required'           => 'كود الحساب مطلوب.',
            'code.unique'             => 'كود الحساب مستخدم بالفعل.',
            'type.required'           => 'نوع الحساب مطلوب.',
            'normal_balance.required' => 'الرصيد الطبيعي مطلوب.',
            'parent_id.exists'        => 'الحساب الأب غير موجود.',
        ];
    }
}
