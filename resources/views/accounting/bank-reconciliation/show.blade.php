@extends('accounting._layout')

@section('title', 'التسوية البنكية — ' . $bankStatement->account->name)

@section('topbar-actions')
    <a href="{{ route('accounting.bank-reconciliation.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">
        ← العودة للقائمة
    </a>
@endsection

@section('content')

{{-- ══════════════════════════════════════════════════════
     Summary bar
     ══════════════════════════════════════════════════════ --}}
<div class="ac-br-summary" id="br-summary"
     data-difference="{{ $summary['difference'] }}"
     data-balanced="{{ $summary['is_balanced'] ? '1' : '0' }}">

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">الحساب</span>
        <span class="ac-br-summary__value">
            <span class="ac-code">{{ $bankStatement->account->code }}</span>
            {{ $bankStatement->account->name }}
        </span>
    </div>

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">تاريخ الكشف</span>
        <span class="ac-br-summary__value">{{ $bankStatement->statement_date->format('Y-m-d') }}</span>
    </div>

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">رصيد الكشف الافتتاحي</span>
        <span class="ac-br-summary__value ac-num">{{ number_format($bankStatement->opening_balance, 2) }}</span>
    </div>

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">رصيد الكشف الختامي</span>
        <span class="ac-br-summary__value ac-num">{{ number_format($bankStatement->closing_balance, 2) }}</span>
    </div>

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">مطابق / إجمالي</span>
        <span class="ac-br-summary__value" id="br-matched-count">
            {{ $summary['matched_count'] }} / {{ $bankStatement->lines->count() }}
        </span>
    </div>

    <div class="ac-br-summary__item ac-br-summary__item--diff">
        <span class="ac-br-summary__label">الفرق</span>
        <span class="ac-br-summary__value ac-num {{ $summary['is_balanced'] ? 'ac-br-diff--ok' : 'ac-br-diff--err' }}"
              id="br-difference">
            {{ number_format($summary['difference'], 2) }}
        </span>
    </div>

    <div class="ac-br-summary__item">
        <span class="ac-br-summary__label">الحالة</span>
        <span class="ac-badge ac-badge--{{ $bankStatement->statusMod() }}">
            {{ $bankStatement->statusLabel() }}
        </span>
    </div>
</div>

{{-- Validation error (from complete) --}}
@if($errors->has('reconciliation'))
    <div class="ac-alert ac-alert--danger" style="margin-bottom:1rem">
        {{ $errors->first('reconciliation') }}
        <button type="button" data-dismiss="alert" class="ac-alert__close">×</button>
    </div>
@endif

{{-- ══════════════════════════════════════════════════════
     Interaction hint
     ══════════════════════════════════════════════════════ --}}
@if(! $bankStatement->isReconciled())
<div class="ac-br-hint" id="br-hint">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/>
        <line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <span id="br-hint-text">اضغط على سطر من كشف البنك (العمود الأيسر) لتحديده، ثم اضغط
        <strong>طابق</strong> بجانب القيد المقابل في العمود الأيمن.</span>
</div>
@endif

{{-- ══════════════════════════════════════════════════════
     Main two-column grid
     ══════════════════════════════════════════════════════ --}}
<div class="ac-br-grid"
     id="br-page"
     data-match-url="{{ route('accounting.bank-reconciliation.match',   $bankStatement) }}"
     data-unmatch-url="{{ route('accounting.bank-reconciliation.unmatch', $bankStatement) }}">

    {{-- ────────────────────────────────────────────
         LEFT: Bank statement lines
         ──────────────────────────────────────────── --}}
    <div class="ac-br-col">
        <div class="ac-br-col__header">
            <h3 class="ac-br-col__title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <line x1="3" y1="9" x2="21" y2="9"/>
                    <line x1="9" y1="21" x2="9" y2="9"/>
                </svg>
                بنود كشف البنك
            </h3>
            <span class="ac-muted" style="font-size:.8rem">
                {{ $bankStatement->lines->count() }} سطر
            </span>
        </div>

        <div class="ac-br-col__body">

            {{-- Unmatched bank lines --}}
            @php $unmatchedBankLines = $bankStatement->lines->where('is_matched', false); @endphp
            @if($unmatchedBankLines->isNotEmpty())
            <div class="ac-br-section-label">غير مطابق ({{ $unmatchedBankLines->count() }})</div>
            @foreach($unmatchedBankLines as $line)
            <div class="ac-br-bank-row {{ $bankStatement->isReconciled() ? '' : 'ac-br-bank-row--selectable' }}"
                 data-line-id="{{ $line->id }}"
                 data-unmatched="1">
                <div class="ac-br-row__main">
                    <span class="ac-br-row__date">{{ $line->transaction_date->format('Y-m-d') }}</span>
                    <span class="ac-br-row__desc">{{ $line->description }}</span>
                </div>
                <div class="ac-br-row__amounts">
                    @if($line->debit > 0)
                        <span class="ac-br-amount ac-br-amount--debit">{{ number_format($line->debit, 2) }}</span>
                    @endif
                    @if($line->credit > 0)
                        <span class="ac-br-amount ac-br-amount--credit">{{ number_format($line->credit, 2) }}</span>
                    @endif
                </div>
            </div>
            @endforeach
            @endif

            {{-- Matched bank lines --}}
            @php $matchedBankLines = $bankStatement->lines->where('is_matched', true); @endphp
            @if($matchedBankLines->isNotEmpty())
            <div class="ac-br-section-label ac-br-section-label--matched">
                مطابق ({{ $matchedBankLines->count() }})
            </div>
            @foreach($matchedBankLines as $line)
            <div class="ac-br-bank-row ac-br-bank-row--matched">
                <div class="ac-br-row__main">
                    <span class="ac-br-row__date">{{ $line->transaction_date->format('Y-m-d') }}</span>
                    <span class="ac-br-row__desc">{{ $line->description }}</span>
                    @if($line->journalLine && $line->journalLine->entry)
                        <span class="ac-br-row__linked">
                            ← {{ $line->journalLine->entry->entry_number }}
                        </span>
                    @endif
                </div>
                <div class="ac-br-row__amounts">
                    @if($line->debit > 0)
                        <span class="ac-br-amount ac-br-amount--debit">{{ number_format($line->debit, 2) }}</span>
                    @endif
                    @if($line->credit > 0)
                        <span class="ac-br-amount ac-br-amount--credit">{{ number_format($line->credit, 2) }}</span>
                    @endif
                    @if(! $bankStatement->isReconciled())
                    <button type="button"
                            class="ac-btn ac-btn--ghost ac-btn--xs ac-br-unmatch-btn"
                            data-unmatch-line="{{ $line->id }}"
                            title="إلغاء المطابقة">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                             width="12" height="12">
                            <line x1="18" y1="6" x2="6" y2="18"/>
                            <line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
            @endif

            @if($bankStatement->lines->isEmpty())
                <div class="ac-br-empty">لا توجد بنود في هذا الكشف.</div>
            @endif

        </div>
    </div>

    {{-- ────────────────────────────────────────────
         RIGHT: Unmatched ledger lines
         ──────────────────────────────────────────── --}}
    <div class="ac-br-col">
        <div class="ac-br-col__header">
            <h3 class="ac-br-col__title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                    <line x1="8" y1="7" x2="16" y2="7"/>
                    <line x1="8" y1="11" x2="13" y2="11"/>
                </svg>
                قيود الدفتر غير المطابقة
            </h3>
            <span class="ac-muted" style="font-size:.8rem">
                {{ $unmatchedJournalLines->count() }} قيد
            </span>
        </div>

        <div class="ac-br-col__body">
            @forelse($unmatchedJournalLines as $jl)
            <div class="ac-br-journal-row" id="jl-{{ $jl->id }}">
                <div class="ac-br-row__main">
                    <span class="ac-br-row__date">{{ $jl->entry->entry_date->format('Y-m-d') }}</span>
                    <span class="ac-code" style="font-size:.78rem">{{ $jl->entry->entry_number }}</span>
                    <span class="ac-br-row__desc">{{ Str::limit($jl->entry->description ?? $jl->description, 60) }}</span>
                </div>
                <div class="ac-br-row__amounts">
                    @if($jl->debit > 0)
                        <span class="ac-br-amount ac-br-amount--debit">{{ number_format($jl->debit, 2) }}</span>
                    @endif
                    @if($jl->credit > 0)
                        <span class="ac-br-amount ac-br-amount--credit">{{ number_format($jl->credit, 2) }}</span>
                    @endif
                    @if(! $bankStatement->isReconciled())
                    <button type="button"
                            class="ac-btn ac-btn--primary ac-btn--xs ac-br-match-btn"
                            data-match-journal="{{ $jl->id }}"
                            title="طابق مع السطر المحدد">
                        طابق
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="ac-br-empty">
                @if($bankStatement->isReconciled())
                    جميع القيود مطابقة. التسوية مكتملة.
                @else
                    لا توجد قيود دفتر غير مطابقة لهذا الحساب.
                @endif
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     Complete button (visible when balanced & open)
     ══════════════════════════════════════════════════════ --}}
@if(! $bankStatement->isReconciled())
<div class="ac-br-complete-bar" id="br-complete-bar"
     style="{{ $summary['is_balanced'] ? '' : 'display:none' }}">
    <div class="ac-br-complete-bar__msg">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"
             style="color:#16a34a">
            <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
            <polyline points="22,4 12,14.01 9,11.01"/>
        </svg>
        الفرق = صفر — التسوية متوازنة ويمكن إغلاقها.
    </div>
    <form method="POST"
          action="{{ route('accounting.bank-reconciliation.complete', $bankStatement) }}"
          id="br-complete-form">
        @csrf
        <button type="submit" class="ac-btn ac-btn--success">
            إغلاق التسوية البنكية
        </button>
    </form>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => BankReconciliation.init());
</script>
@endpush
