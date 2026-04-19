<?php

namespace App\Modules\Accounting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Accounting\Http\Requests\StoreAccountRequest;
use App\Modules\Accounting\Http\Requests\UpdateAccountRequest;
use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function __construct(private readonly ActivityLogService $log) {}

    public function index(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $accounts = Account::forTenant($companyId)
            ->with('parent:id,name,code')
            ->withCount('children')
            ->orderBy('code')
            ->get();

        // Single bulk balance query
        $balanceMap = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.tenant_id', $companyId)
            ->where('journal_entries.status', 'posted')
            ->selectRaw('account_id, SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->groupBy('account_id')
            ->get()
            ->keyBy('account_id');

        $usedIds = DB::table('journal_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.tenant_id', $companyId)
            ->distinct()
            ->pluck('journal_lines.account_id')
            ->flip();

        $accounts->each(function (Account $account) use ($balanceMap, $usedIds) {
            $row    = $balanceMap->get($account->id);
            $debit  = (float) ($row->total_debit  ?? 0);
            $credit = (float) ($row->total_credit ?? 0);

            $account->balance = $account->normal_balance === 'debit'
                ? $debit - $credit
                : $credit - $debit;

            $account->deletable = !isset($usedIds[$account->id])
                && $account->children_count === 0
                && !$account->is_system;
        });

        $typeOrder = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        $grouped   = collect($typeOrder)
            ->mapWithKeys(fn ($type) => [
                $type => $accounts->where('type', $type)->values(),
            ])
            ->filter(fn ($group) => $group->isNotEmpty());

        return view('accounting.accounts.index', compact('grouped'));
    }

    public function create(Request $request): View
    {
        $companyId = $request->user()->company_id;

        $parentAccount = null;
        if ($request->filled('parent_id')) {
            $parentAccount = Account::forTenant($companyId)
                ->find($request->integer('parent_id'));
        }

        $parents = Account::forTenant($companyId)
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        return view('accounting.accounts.create', compact('parents', 'parentAccount'));
    }

    public function store(StoreAccountRequest $request): RedirectResponse
    {
        Gate::authorize('can-write');

        $data      = $request->validated();
        $companyId = $request->user()->company_id;

        if (!empty($data['parent_id'])) {
            $parent = Account::forTenant($companyId)->findOrFail((int) $data['parent_id']);
            $data['type']           = $parent->type;
            $data['normal_balance'] = $parent->normal_balance;
            $data['code']           = $this->nextChildCode($parent, $companyId);
        }

        $account = Account::create([...$data, 'tenant_id' => $companyId]);

        $this->log->log(
            $companyId, 'created', 'account', $account->id,
            $account->code, "أنشأ الحساب [{$account->name}]."
        );

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'تم إنشاء الحساب بنجاح.');
    }

    public function edit(Request $request, Account $account): View
    {
        $this->authorizeCompany($request, $account);
        $account->load('parent:id,name,code');
        return view('accounting.accounts.edit', compact('account'));
    }

    public function update(UpdateAccountRequest $request, Account $account): RedirectResponse
    {
        Gate::authorize('can-write');
        $this->authorizeCompany($request, $account);

        $account->update($request->validated());

        $this->log->log(
            $request->user()->company_id, 'updated', 'account', $account->id,
            $account->code, "عدَّل الحساب [{$account->name}]."
        );

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', 'تم تحديث الحساب بنجاح.');
    }

    public function destroy(Request $request, Account $account): RedirectResponse
    {
        Gate::authorize('admin-only');
        $this->authorizeCompany($request, $account);

        if ($account->is_system) {
            return back()->withErrors(['account' => 'لا يمكن حذف الحسابات المدمجة في النظام.']);
        }

        $childCount = Account::where('tenant_id', $request->user()->company_id)
            ->where('parent_id', $account->id)
            ->count();

        if ($childCount > 0) {
            return back()->withErrors(['account' => 'لا يمكن حذف هذا الحساب لأنه يحتوي على حسابات فرعية. احذف الحسابات الفرعية أولاً.']);
        }

        $hasTransactions = DB::table('journal_lines')
            ->where('account_id', $account->id)
            ->exists();

        if ($hasTransactions) {
            return back()->withErrors(['account' => 'لا يمكن حذف هذا الحساب لأنه مرتبط بمعاملات. يمكنك إيقافه بدلاً من الحذف.']);
        }

        $name = $account->name;
        $code = $account->code;

        $this->log->log(
            $request->user()->company_id, 'deleted', 'account', $account->id,
            $code, "حذف الحساب [{$name}]."
        );

        $account->delete();

        return redirect()
            ->route('accounting.accounts.index')
            ->with('success', "تم حذف الحساب [{$name}] بنجاح.");
    }

    public function toggleActive(Request $request, Account $account): RedirectResponse
    {
        Gate::authorize('admin-only');
        $this->authorizeCompany($request, $account);

        if ($account->is_system) {
            return back()->withErrors(['account' => 'لا يمكن إيقاف الحسابات المدمجة في النظام.']);
        }

        $account->update(['is_active' => !$account->is_active]);
        $isNowActive = $account->fresh()->is_active;

        $this->log->log(
            $request->user()->company_id,
            $isNowActive ? 'activated' : 'deactivated',
            'account', $account->id,
            $account->code,
            $isNowActive ? "فعَّل الحساب [{$account->name}]." : "أوقف الحساب [{$account->name}]."
        );

        return back()->with('success', $isNowActive ? 'تم تفعيل الحساب.' : 'تم إيقاف الحساب.');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function nextChildCode(Account $parent, int $companyId): string
    {
        $maxCode = Account::where('tenant_id', $companyId)
            ->where('parent_id', $parent->id)
            ->max('code');

        return (string) ((int) ($maxCode ?? $parent->code) + 10);
    }

    private function authorizeCompany(Request $request, Account $account): void
    {
        abort_if($account->tenant_id !== $request->user()->company_id, 403);
    }
}
