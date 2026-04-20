<?php

namespace App\Modules\Accounting\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyModule extends Model
{
    protected $table = 'company_modules';

    protected $fillable = [
        'company_id',
        'module_key',
        'label',
        'is_enabled',
    ];

    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'is_enabled' => 'boolean',
        ];
    }
}
