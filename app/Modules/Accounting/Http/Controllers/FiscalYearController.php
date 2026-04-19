<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Models\FiscalYear;
use App\Modules\Accounting\Services\ActivityLogService;
use App\Modules\Accounting\Services\FiscalYearCloseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function __construct(
        private readonly FiscalYearCloseService $service,
        private readonly ActivityLogService     $log,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $openYear   = FiscalYear::forCompany($companyId)->open()->latest('year')->first();
        $closedYears = FiscalYear::forCompany($companyId)->closed()->orderByDesc('year')->get();

        $preview = null;
        if ($openYear) {
            $preview = $this->service->preview($openYear);
        }

        // Suggest next year to create: highest year + 1, or current year
        $lastYear   = FiscalYear::forCompany($companyId)->max('year');
        $suggestYear = $lastYear ? $lastYear + 1 : now()->year;

        return view('accounting.fiscal-years.index', compact(
            'openYear',
            'closedYears',
            'preview',
            'suggestYear',
        ));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'year'       => ['required', 'integer', 'min:2000', 'max:2100'],
            'starts_at'  => ['required', 'date'],
            'ends_at'    => ['required', 'date', 'after:starts_at'],
        ], [
            'year.required'      => 'السنة مطلوبة.',
            'year.integer'       => 'السنة يجب أن تكون رقماً.',
            'starts_at.required' => 'تاريخ البداية مطلوب.',
            'ends_at.required'   => 'تاريخ النهاية مطلوب.',
            'ends_at.after'      => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية.',
        ]);

        // Only one open year per company at a time
        $existingOpen = FiscalYear::forCompany($companyId)->open()->exists();
        if ($existingOpen) {
            return back()->withErrors(['year' => 'يوجد سنة مالية مفتوحة بالفعل. أغلقها أولاً قبل إنشاء سنة جديدة.']);
        }

        // No duplicate year
        $duplicate = FiscalYear::where('company_id', $companyId)
            ->where('year', $validated['year'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors(['year' => "السنة المالية {$validated['year']} موجودة بالفعل."]);
        }

        $fiscalYear = FiscalYear::create([
            'company_id' => $companyId,
            'year'       => $validated['year'],
            'starts_at'  => $validated['starts_at'],
            'ends_at'    => $validated['ends_at'],
            'status'     => 'open',
        ]);

        $this->log->log(
            $companyId, 'created', 'fiscal_year', $fiscalYear->id,
            "سنة {$fiscalYear->year}",
            "فتح سنة مالية جديدة [{$fiscalYear->year}]."
        );

        return redirect()
            ->route('accounting.fiscal-years.index')
            ->with('success', "تم إنشاء السنة المالية {$fiscalYear->year} بنجاح.");
    }

    // -------------------------------------------------------------------------

    public function close(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        Gate::authorize('can-write');
        abort_if($fiscalYear->company_id !== $request->user()->company_id, 403);

        if ($fiscalYear->isClosed()) {
            return back()->with('info', 'هذه السنة المالية مغلقة بالفعل.');
        }

        try {
            $result = $this->service->close($fiscalYear);
        } catch (\DomainException $e) {
            return back()->withErrors(['close' => $e->getMessage()]);
        }

        $profitLabel = $result['netProfit'] >= 0
            ? 'ربح ' . number_format($result['netProfit'], 2)
            : 'خسارة ' . number_format(abs($result['netProfit']), 2);

        $this->log->log(
            $request->user()->company_id,
            'closed', 'fiscal_year', $fiscalYear->id,
            "سنة {$fiscalYear->year}",
            "أغلق السنة المالية [{$fiscalYear->year}] — صافي: {$profitLabel}."
        );

        return redirect()
            ->route('accounting.fiscal-years.index')
            ->with('success',
                "تم إغلاق السنة المالية {$fiscalYear->year} بنجاح. " .
                "صافي النتيجة: {$profitLabel}."
            );
    }
}
