<?php

namespace App\Modules\Accounting\Http\Middleware;

use App\Modules\Accounting\Services\CompanyModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleEnabled
{
    public function __construct(
        private readonly CompanyModuleService $modules,
    ) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if (! $user || ! $user->company_id) {
            abort(403);
        }

        if (! $this->modules->isEnabled((int) $user->company_id, $moduleKey)) {
            abort(403, 'هذا القسم غير مفعل لشركتك.');
        }

        return $next($request);
    }
}
