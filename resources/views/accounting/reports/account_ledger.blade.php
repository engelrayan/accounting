@extends('accounting._layout')

@section('title', 'حركات الحساب')

@php
$typeLabels = [
    'asset'     => 'أصول',
    'liability' => 'التزامات',
    'equity'    => 'حقوق ملكية',
    'revenue'   => 'إيرادات',
    'expense'   => 'مصروفات',
];
$fmt = fn(float $n) => number_format(abs($n), 2);
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.reports.trial-balance') }}{{ ($from || $to) ? '?from='.($from??'').'&to='.($to??'') : '' }}"
       class="ac-btn ac-btn--secondary ac-btn--sm">
        ← ميزان المراجعة
    </a>
@endsection

@section('content')

{{-- ══ Account info header ══════════════════════════════════════════════════ --}}
<div class="ac-ledger-header">
    <div class="ac-ledger-header__info">
        <code class="ac-code-tag ac-code-tag--lg">{{ $account->code }}</code>
        <div>
            <div class="ac-ledger-header__name">{{ $account->name }}</div>
            <div class="ac-ledger-header__meta">
                <span>{{ $typeLabels[$account->type] ?? $account->type }}</span>
                <span class="ac-dot">·</span>
                <span>{{ $account->normal_balance === 'debit' ? 'رصيد طبيعي: مدين' : 'رصيد طبيعي: دائن' }}</span>
                @if(!$account->is_active)
                    <span class="ac-dot">·</span>
                    <span class="ac-badge ac-badge--off">موقوف</span>
                @endif
            </div>
        </div>
    </div>
    <div class="ac-ledger-header__balance {{ $runningBalance < 0 ? 'ac-text-danger' : '' }}">
        <span class="ac-ledger-header__balance-label">الرصيد الختامي</span>
        <span class="ac-ledger-header__balance-value">{{ $fmt($runningBalance) }}</span>
        <span class="ac-dr-cr-badge ac-dr-cr-badge--{{ $account->normal_balance }}">
            {{ $account->normal_balance === 'debit' ? 'مدين' : 'دائن' }}
        </span>
    </div>
</div>

{{-- ══ Date filter ══════════════════════════════════════════════════════════ --}}
<div class="ac-card ac-report-filter-card">
    <div class="ac-card__body">
        <form method="GET"
              action="{{ route('accounting.reports.account-ledger', $account->id) }}"
              class="ac-report-filter">
            <div class="ac-form-row">
                <div class="ac-form-group">
                    <label class="ac-label" for="from">من تاريخ</label>
                    <input id="from" name="from" type="date" class="ac-control"
                           value="{{ $from ?? '' }}">
                </div>
                <div class="ac-form-group">
                    <label class="ac-label" for="to">إلى تاريخ</label>
                    <input id="to" name="to" type="date" class="ac-control"
                           value="{{ $to ?? '' }}">
                </div>
                <div class="ac-form-group ac-form-group--end">
                    <button type="submit" class="ac-btn ac-btn--primary">تطبيق</button>
                    @if($from || $to)
                        <a href="{{ route('accounting.reports.account-ledger', $account->id) }}"
                           class="ac-btn ac-btn--secondary">مسح</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ══ Ledger lines ══════════════════════════════════════════════════════════ --}}
@if($lines->isEmpty())
    <div class="ac-empty">
        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
            <polyline points="14,2 14,8 20,8"/>
        </svg>
        <p>لا توجد حركات مُرحَّلة لهذا الحساب في الفترة المحددة.</p>
    </div>
@else

<div class="ac-card">
    <div class="ac-card__body ac-card__body--flush">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>التاريخ</th>
                    <th>رقم القيد</th>
                    <th>البيان</th>
                    <th class="ac-table__num">مدين</th>
                    <th class="ac-table__num">دائن</th>
                    <th class="ac-table__num">الرصيد</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lines as $line)
                <tr>
                    <td class="ac-table__muted">{{ $line->entry_date }}</td>
                    <td><code class="ac-code-tag">{{ $line->entry_number }}</code></td>
                    <td>{{ $line->line_description ?: $line->entry_description ?: '—' }}</td>
                    <td class="ac-table__num">
                        @if($line->debit > 0)
                            <span class="ac-ledger__debit">{{ number_format($line->debit, 2) }}</span>
                        @else
                            <span class="ac-table__muted">—</span>
                        @endif
                    </td>
                    <td class="ac-table__num">
                        @if($line->credit > 0)
                            <span class="ac-ledger__credit">{{ number_format($line->credit, 2) }}</span>
                        @else
                            <span class="ac-table__muted">—</span>
                        @endif
                    </td>
                    <td class="ac-table__num ac-table__amount {{ $line->running_balance < 0 ? 'ac-text-danger' : '' }}">
                        {{ $fmt($line->running_balance) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="ac-table__totals-row">
                    <td colspan="3" class="ac-table__totals-label">الإجمالي</td>
                    <td class="ac-table__num ac-ledger__debit">{{ number_format($totalDebit, 2) }}</td>
                    <td class="ac-table__num ac-ledger__credit">{{ number_format($totalCredit, 2) }}</td>
                    <td class="ac-table__num ac-table__amount {{ $runningBalance < 0 ? 'ac-text-danger' : '' }}">
                        {{ $fmt($runningBalance) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@endif
@endsection
