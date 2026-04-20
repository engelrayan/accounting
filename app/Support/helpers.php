<?php

use App\Modules\Accounting\Services\CompanyModuleService;

if (! function_exists('getCompanyModules')) {
    function getCompanyModules(?int $companyId = null): array
    {
        $companyId ??= (int) auth()->user()?->company_id;

        if ($companyId <= 0) {
            return [];
        }

        return app(CompanyModuleService::class)->forCompany($companyId);
    }
}

if (! function_exists('companyModuleEnabled')) {
    function companyModuleEnabled(string $moduleKey, ?int $companyId = null): bool
    {
        $companyId ??= (int) auth()->user()?->company_id;

        if ($companyId <= 0) {
            return true;
        }

        return app(CompanyModuleService::class)->isEnabled($companyId, $moduleKey);
    }
}
