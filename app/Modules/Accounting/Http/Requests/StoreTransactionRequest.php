<?php

namespace App\Modules\Accounting\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;
        $type      = $this->input('type');

        $accountIn = fn () => Rule::exists('accounts', 'id')
            ->where('tenant_id', $companyId)
            ->where('is_active', true);

        $partnerIn = Rule::exists('partners', 'id')
            ->where('company_id', $companyId);

        $base = [
            'type'             => ['required', 'in:expense,income,transfer,capital_addition,withdrawal'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'transaction_date' => ['required', 'date'],
            'description'      => ['nullable', 'string', 'max:255'],
            'attachments'      => ['nullable', 'array', 'max:10'],
            'attachments.*'    => [
                'file',
                'max:5120',                                       // 5 MB per file
                'mimes:jpg,jpeg,png,gif,webp,pdf,xlsx,csv,xls',  // strict whitelist
            ],
        ];

        $typeRules = match ($type) {
            'expense' => [
                'cash_account_id'    => ['required', 'integer', $accountIn()],
                'expense_account_id' => ['required', 'integer', $accountIn()],
            ],
            'income' => [
                'revenue_account_id' => ['required', 'integer', $accountIn()],
                'cash_account_id'    => ['required', 'integer', $accountIn()],
            ],
            'transfer' => [
                'from_account_id' => ['required', 'integer', $accountIn()],
                'to_account_id'   => ['required', 'integer', $accountIn(), 'different:from_account_id'],
            ],
            'capital_addition', 'withdrawal' => [
                'partner_id'      => ['required', 'integer', $partnerIn],
                'cash_account_id' => ['required', 'integer', $accountIn()],
            ],
            default => [],
        };

        return array_merge($base, $typeRules);
    }

    public function messages(): array
    {
        return [
            'type.required'                => 'نوع العملية مطلوب.',
            'type.in'                      => 'نوع العملية غير صحيح.',
            'amount.required'              => 'المبلغ مطلوب.',
            'amount.min'                   => 'يجب أن يكون المبلغ أكبر من صفر.',
            'transaction_date.required'    => 'التاريخ مطلوب.',
            'cash_account_id.required'     => 'يرجى تحديد الخزنة أو البنك.',
            'expense_account_id.required'  => 'يرجى تحديد نوع المصروف.',
            'revenue_account_id.required'  => 'يرجى تحديد نوع الإيراد.',
            'from_account_id.required'     => 'حساب المصدر مطلوب.',
            'to_account_id.required'       => 'حساب الوجهة مطلوب.',
            'to_account_id.different'      => 'لا يمكن أن يكون حساب المصدر والوجهة نفس الحساب.',
            'partner_id.required'          => 'يرجى تحديد الشريك.',
            '*.exists'                     => 'الحساب أو الشريك المحدد غير موجود.',
            'attachments.max'              => 'لا يمكن رفع أكثر من 10 ملفات في معاملة واحدة.',
            'attachments.*.file'           => 'أحد الملفات المرفقة غير صالح.',
            'attachments.*.max'            => 'حجم كل ملف يجب أن لا يتجاوز 5 ميجابايت.',
            'attachments.*.mimes'          => 'نوع ملف غير مدعوم. المسموح: صور، PDF، Excel.',
        ];
    }
}
