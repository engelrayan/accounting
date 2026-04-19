<?php

namespace App\Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        $existsInCompany = fn (string $col = 'id') =>
            Rule::exists('accounts', $col)->where('tenant_id', $companyId);

        return [
            'name'          => ['required', 'string', 'max:150'],
            'category'      => ['required', 'string', 'in:vehicle,equipment,furniture,building,other'],
            'purchase_date' => ['required', 'date'],
            'purchase_cost' => ['required', 'numeric', 'min:0.01'],
            'salvage_value' => ['nullable', 'numeric', 'min:0'],
            'useful_life'   => ['required', 'integer', 'min:1'],
            'payment_account_id' => ['required', 'integer', $existsInCompany()],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'اسم الأصل مطلوب.',
            'category.required'          => 'فئة الأصل مطلوبة.',
            'category.in'                => 'فئة الأصل غير صحيحة.',
            'purchase_date.required'     => 'تاريخ الشراء مطلوب.',
            'purchase_cost.required'     => 'تكلفة الشراء مطلوبة.',
            'purchase_cost.min'          => 'يجب أن تكون تكلفة الشراء أكبر من صفر.',
            'useful_life.required'       => 'العمر الافتراضي مطلوب.',
            'useful_life.min'            => 'يجب أن يكون العمر الافتراضي شهراً واحداً على الأقل.',
            'payment_account_id.required'=> 'حساب الدفع مطلوب.',
        ];
    }
}
