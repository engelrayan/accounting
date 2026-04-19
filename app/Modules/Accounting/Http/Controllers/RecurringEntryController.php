<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\RecurringJournalEntry;
use App\Modules\Accounting\Services\RecurringEntryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class RecurringEntryController extends Controller
{
    public function __construct(
        private readonly RecurringEntryService $service,
    ) {}

    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $entries = RecurringJournalEntry::forCompany($companyId)
            ->orderByDesc('is_active')
            ->orderBy('next_run_date')
            ->paginate(20);

        $dueCount = RecurringJournalEntry::forCompany($companyId)->due()->count();

        return view('accounting.recurring.index', compact('entries', 'dueCount'));
    }

    // -------------------------------------------------------------------------

    public function create(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $accounts = Account::where('tenant_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.recurring.create', compact('accounts'));
    }

    // -------------------------------------------------------------------------

    public function store(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $request->validate([
            'description'   => ['required', 'string', 'max:191'],
            'frequency'     => ['required', 'in:daily,weekly,monthly,quarterly,yearly'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['nullable', 'date', 'after:start_date'],
            'lines'         => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'integer'],
            'lines.*.type'       => ['required', 'in:debit,credit'],
            'lines.*.amount'     => ['required', 'numeric', 'min:0.01'],
        ], [
            'description.required' => 'وصف القيد مطلوب',
            'frequency.required'   => 'تكرار القيد مطلوب',
            'start_date.required'  => 'تاريخ البداية مطلوب',
            'lines.required'       => 'يجب إضافة سطور القيد',
            'lines.min'            => 'يجب إضافة سطرين على الأقل',
        ]);

        try {
            $this->service->create($companyId, $request->all(), $request->user()->id);
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['lines' => $e->getMessage()]);
        }

        return redirect()
            ->route('accounting.recurring.index')
            ->with('success', 'تم إنشاء القيد المتكرر');
    }

    // -------------------------------------------------------------------------

    public function show(RecurringJournalEntry $recurringEntry, Request $request): View
    {
        $companyId = $request->user()->company_id;

        if ($recurringEntry->company_id !== $companyId) abort(403);

        // Load account names for each line
        $accountIds = collect($recurringEntry->lines)->pluck('account_id')->unique()->toArray();
        $accounts   = Account::whereIn('id', $accountIds)->pluck('name', 'id');

        return view('accounting.recurring.show', compact('recurringEntry', 'accounts'));
    }

    // -------------------------------------------------------------------------

    public function toggle(RecurringJournalEntry $recurringEntry, Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        if ($recurringEntry->company_id !== $companyId) abort(403);

        $this->service->toggle($recurringEntry);

        $state = $recurringEntry->fresh()->is_active ? 'تفعيل' : 'إيقاف';

        return back()->with('success', "تم {$state} القيد المتكرر");
    }

    // -------------------------------------------------------------------------

    /** Run all due recurring entries now (manual trigger) */
    public function runDue(Request $request): RedirectResponse
    {
        $companyId = $request->user()->company_id;

        $count = $this->service->generateDue($companyId);

        return back()->with('success', "تم توليد {$count} قيد محاسبي تلقائي");
    }
}
