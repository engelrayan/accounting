<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\CompanyModule;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompanyModuleService
{
    public function forCompany(int $companyId): array
    {
        return Cache::rememberForever($this->cacheKey($companyId), function () use ($companyId) {
            $defaults = $this->defaultModules();
            $overrides = CompanyModule::query()
                ->where('company_id', $companyId)
                ->get()
                ->keyBy('module_key');

            return collect($defaults)
                ->map(function (array $module, string $key) use ($overrides) {
                    $override = $overrides->get($key);
                    $customLabel = $override?->label;

                    return array_merge($module, [
                        'key' => $key,
                        'label' => filled($customLabel) ? $customLabel : $module['default_label'],
                        'custom_label' => $customLabel,
                        'is_enabled' => $override ? (bool) $override->is_enabled : true,
                    ]);
                })
                ->values()
                ->all();
        });
    }

    public function allForSettings(int $companyId): array
    {
        return $this->forCompany($companyId);
    }

    public function isEnabled(int $companyId, string $moduleKey): bool
    {
        $this->assertKnownModule($moduleKey);

        $module = collect($this->forCompany($companyId))
            ->firstWhere('key', $moduleKey);

        return (bool) ($module['is_enabled'] ?? true);
    }

    public function updateForCompany(int $companyId, array $modules): void
    {
        $defaults = $this->defaultModules();
        $now = now();

        DB::transaction(function () use ($companyId, $modules, $defaults, $now) {
            foreach ($defaults as $key => $default) {
                $input = $modules[$key] ?? [];
                $label = trim((string) ($input['label'] ?? ''));

                CompanyModule::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'module_key' => $key,
                    ],
                    [
                        'label' => $label !== '' ? $label : null,
                        'is_enabled' => (bool) ($input['is_enabled'] ?? false),
                        'updated_at' => $now,
                    ],
                );
            }
        });

        $this->forgetCompany($companyId);
    }

    public function resetForCompany(int $companyId): void
    {
        CompanyModule::query()
            ->where('company_id', $companyId)
            ->delete();

        $this->forgetCompany($companyId);
    }

    public function keys(): array
    {
        return array_keys($this->defaultModules());
    }

    public function defaultModules(): array
    {
        return config('modules', []);
    }

    public function forgetCompany(int $companyId): void
    {
        Cache::forget($this->cacheKey($companyId));
    }

    private function assertKnownModule(string $moduleKey): void
    {
        if (! array_key_exists($moduleKey, $this->defaultModules())) {
            throw new InvalidArgumentException("Unknown module key [{$moduleKey}].");
        }
    }

    private function cacheKey(int $companyId): string
    {
        return "company_modules:{$companyId}";
    }
}
