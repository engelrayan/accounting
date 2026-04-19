<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\CompanySettings;

class CompanySettingsService
{
    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Get the settings object for a company.
     * Creates a default row if none exists yet.
     */
    public function forCompany(int $companyId): CompanySettings
    {
        return CompanySettings::firstOrCreate(
            ['company_id' => $companyId],
            ['settings'   => CompanySettings::defaults()],
        );
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Merge incoming key-value pairs into the stored settings JSON.
     * Only known keys (present in defaults) are accepted — everything else
     * is silently dropped to prevent mass-assignment of arbitrary data.
     */
    public function update(int $companyId, array $input): CompanySettings
    {
        $record   = $this->forCompany($companyId);
        $current  = $record->settings ?? CompanySettings::defaults();
        $allowed  = array_keys(CompanySettings::defaults());

        foreach ($allowed as $key) {
            if (array_key_exists($key, $input)) {
                // Booleans come from form as "1"/"0" strings
                if ($key === 'tax_enabled') {
                    $current[$key] = (bool) $input[$key];
                } elseif ($key === 'tax_rate') {
                    $current[$key] = round((float) $input[$key], 2);
                } else {
                    $current[$key] = $input[$key];
                }
            }
        }

        $record->settings = $current;
        $record->save();

        return $record;
    }
}
