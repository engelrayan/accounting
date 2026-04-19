<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\Account;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Models\Partner;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(private readonly JournalEntryService $journalService) {}

    // -------------------------------------------------------------------------
    // Create partner — auto-creates capital + drawing accounts
    // -------------------------------------------------------------------------

    public function createPartner(array $data, int $companyId): Partner
    {
        return DB::transaction(function () use ($data, $companyId) {
            $capitalParent = Account::where('tenant_id', $companyId)->where('code', '3100')->first();
            $drawingParent = Account::where('tenant_id', $companyId)->where('code', '3200')->first();

            $capitalAccount = Account::create([
                'tenant_id'      => $companyId,
                'parent_id'      => $capitalParent?->id,
                'code'           => $this->nextCapitalCode($companyId),
                'name'           => $data['name'] . ' - رأس المال',
                'type'           => 'equity',
                'normal_balance' => 'credit',
                'is_system'      => false,
                'is_active'      => true,
            ]);

            $drawingAccount = Account::create([
                'tenant_id'      => $companyId,
                'parent_id'      => $drawingParent?->id,
                'code'           => $this->nextDrawingCode($companyId),
                'name'           => $data['name'] . ' - مسحوبات',
                'type'           => 'equity',
                'normal_balance' => 'debit',
                'is_system'      => false,
                'is_active'      => true,
            ]);

            return Partner::create([
                'company_id'        => $companyId,
                'name'              => $data['name'],
                'phone'             => $data['phone']  ?? null,
                'email'             => $data['email']  ?? null,
                'notes'             => $data['notes']  ?? null,
                'capital_account_id' => $capitalAccount->id,
                'drawing_account_id' => $drawingAccount->id,
                'created_at'         => now(),
            ]);
        });
    }

    // -------------------------------------------------------------------------
    // Add capital — DR Cash / CR Partner Capital
    // -------------------------------------------------------------------------

    public function addCapital(
        Partner $partner,
        float $amount,
        int $cashAccountId,
        string $date,
        ?string $description = null,
    ): JournalEntry {
        $entry = $this->journalService->createEntry(
            [
                'company_id'     => $partner->company_id,
                'description'    => $description ?? "إضافة رأس مال — {$partner->name}",
                'entry_date'     => $date,
                'reference_type' => 'partner',
                'reference_id'   => $partner->id,
            ],
            [
                ['account_id' => $cashAccountId,               'debit' => $amount, 'credit' => 0],
                ['account_id' => $partner->capital_account_id, 'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->journalService->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Withdraw — DR Partner Drawings / CR Cash
    // -------------------------------------------------------------------------

    public function withdraw(
        Partner $partner,
        float $amount,
        int $cashAccountId,
        string $date,
        ?string $description = null,
    ): JournalEntry {
        $entry = $this->journalService->createEntry(
            [
                'company_id'     => $partner->company_id,
                'description'    => $description ?? "سحب — {$partner->name}",
                'entry_date'     => $date,
                'reference_type' => 'partner',
                'reference_id'   => $partner->id,
            ],
            [
                ['account_id' => $partner->drawing_account_id, 'debit' => $amount, 'credit' => 0],
                ['account_id' => $cashAccountId,               'debit' => 0,       'credit' => $amount],
            ]
        );

        return $this->journalService->postEntry($entry);
    }

    // -------------------------------------------------------------------------
    // Calculations — always from journal_lines, never stored
    // -------------------------------------------------------------------------

    /** Net capital contributed (credit − debit on capital account) */
    public function getPartnerCapital(int $partnerId): float
    {
        $partner = Partner::findOrFail($partnerId);

        return (float) JournalLine::join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $partner->capital_account_id)
            ->selectRaw('COALESCE(SUM(journal_lines.credit) - SUM(journal_lines.debit), 0) as total')
            ->value('total');
    }

    /** Total withdrawals (debit − credit on drawing account) */
    public function getPartnerDrawings(int $partnerId): float
    {
        $partner = Partner::findOrFail($partnerId);

        return (float) JournalLine::join('journal_entries', 'journal_entries.id', '=', 'journal_lines.journal_entry_id')
            ->where('journal_entries.status', 'posted')
            ->where('journal_lines.account_id', $partner->drawing_account_id)
            ->selectRaw('COALESCE(SUM(journal_lines.debit) - SUM(journal_lines.credit), 0) as total')
            ->value('total');
    }

    /** Net balance = capital − drawings */
    public function getPartnerBalance(int $partnerId): float
    {
        return $this->getPartnerCapital($partnerId) - $this->getPartnerDrawings($partnerId);
    }

    // -------------------------------------------------------------------------
    // Ownership percentage
    // -------------------------------------------------------------------------

    public function getPartnerPercentage(int $partnerId): float
    {
        $partner = Partner::findOrFail($partnerId);

        $allPartners  = Partner::forCompany($partner->company_id)->get();
        $totalCapital = $allPartners->sum(fn ($p) => $this->getPartnerCapital($p->id));

        if ($totalCapital <= 0) {
            return 0.0;
        }

        return round($this->getPartnerCapital($partnerId) / $totalCapital * 100, 2);
    }

    // -------------------------------------------------------------------------
    // Partner ledger — all posted entries touching capital or drawing account
    // -------------------------------------------------------------------------

    public function getPartnerLedger(int $partnerId): Collection
    {
        $partner    = Partner::findOrFail($partnerId);
        $accountIds = [$partner->capital_account_id, $partner->drawing_account_id];

        return JournalEntry::where('tenant_id', $partner->company_id)
            ->where('status', 'posted')
            ->whereHas('lines', fn ($q) => $q->whereIn('account_id', $accountIds))
            ->with([
                'lines' => fn ($q) => $q->whereIn('account_id', $accountIds)->with('account'),
            ])
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();
    }

    // -------------------------------------------------------------------------
    // Account code helpers
    // -------------------------------------------------------------------------

    private function nextCapitalCode(int $companyId): string
    {
        $max = Account::where('tenant_id', $companyId)
            ->where('code', 'like', '31%')
            ->max('code');

        return $max ? (string) ((int) $max + 10) : '3110';
    }

    private function nextDrawingCode(int $companyId): string
    {
        $max = Account::where('tenant_id', $companyId)
            ->where('code', 'like', '32%')
            ->max('code');

        return $max ? (string) ((int) $max + 10) : '3210';
    }
}
