<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySettings extends Model
{
    protected $table = 'company_settings';

    protected $fillable = ['company_id', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    // -------------------------------------------------------------------------
    // Defaults — used when no row exists yet for this company
    // -------------------------------------------------------------------------

    public static function defaults(): array
    {
        return [
            'tax_enabled'         => false,
            'tax_rate'            => 14.0,
            'tax_name'            => 'ضريبة القيمة المضافة',
            'tax_account_code'    => '2300',
            'tax_number'          => '',
            'currency'            => 'EGP',
            'fiscal_year_start'   => '01-01',
            'invoice_footer_note' => '',
            'company_name_ar'     => '',
            'company_address'     => '',
        ];
    }

    // -------------------------------------------------------------------------
    // Typed accessors — always return a value even if key is missing in JSON
    // -------------------------------------------------------------------------

    public function get(string $key, mixed $fallback = null): mixed
    {
        $settings = $this->settings ?? [];
        return $settings[$key] ?? (static::defaults()[$key] ?? $fallback);
    }

    public function taxEnabled(): bool
    {
        return (bool) $this->get('tax_enabled', false);
    }

    public function taxRate(): float
    {
        return (float) $this->get('tax_rate', 14.0);
    }

    public function taxName(): string
    {
        return (string) $this->get('tax_name', 'ضريبة القيمة المضافة');
    }

    public function currency(): string
    {
        return (string) $this->get('currency', 'EGP');
    }
}
