@extends('accounting._layout')

@section('title', 'حركة محاسبية ' . $journalEntry->entry_number)

@section('topbar-actions')
<div class="ac-topbar-actions">
    <a href="{{ route('accounting.journal-entries.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>
@endsection

@section('content')

@php
    $refType = $journalEntry->reference_type;
    $emoji   = \App\Modules\Accounting\Models\JournalEntry::typeEmoji($refType);
    $label   = \App\Modules\Accounting\Models\JournalEntry::typeLabel($refType);
    $typeMod = \App\Modules\Accounting\Models\JournalEntry::typeMod($refType);
    $totalDebit  = $journalEntry->lines->sum('debit');
    $totalCredit = $journalEntry->lines->sum('credit');
    $isBalanced  = bccomp((string)$totalDebit, (string)$totalCredit, 2) === 0;
@endphp

<div class="ac-je-show-layout">

    {{-- ══ Main column ═════════════════════════════════════════════════════════ --}}
    <div class="ac-je-show-main">

        {{-- ── Header card ── --}}
        <div class="ac-card ac-je-header-card">
            <div class="ac-card__body">
                <div class="ac-je-header-card__top">
                    <div class="ac-je-header-card__info">
                        <span class="ac-je-type-badge ac-je-type-badge--{{ $typeMod }} ac-je-type-badge--lg">
                            <span class="ac-je-type-badge__emoji">{{ $emoji }}</span>
                            <span class="ac-je-type-badge__label">{{ $label }}</span>
                        </span>
                        <h2 class="ac-je-entry-number">{{ $journalEntry->entry_number }}</h2>
                    </div>
                    <div class="ac-je-header-card__status">
                        @if($journalEntry->status === 'posted')
                            <span class="ac-badge ac-badge--posted ac-badge--lg">✔ مُرحَّل</span>
                        @elseif($journalEntry->status === 'reversed')
                            <span class="ac-badge ac-badge--reversed ac-badge--lg">↩ معكوس</span>
                        @else
                            <span class="ac-badge ac-badge--draft ac-badge--lg">◌ مسوَّدة</span>
                        @endif
                    </div>
                </div>

                <p class="ac-je-description">{{ $journalEntry->description }}</p>

                {{-- Meta row --}}
                <div class="ac-je-meta-row">
                    <div class="ac-je-meta-item">
                        <span class="ac-je-meta-item__key">التاريخ</span>
                        <span class="ac-je-meta-item__val">{{ $journalEntry->entry_date->format('Y/m/d') }}</span>
                    </div>
                    <div class="ac-je-meta-item">
                        <span class="ac-je-meta-item__key">بواسطة</span>
                        <span class="ac-je-meta-item__val">{{ $journalEntry->creator?->name ?? 'النظام' }}</span>
                    </div>
                    @if($journalEntry->posted_at)
                    <div class="ac-je-meta-item">
                        <span class="ac-je-meta-item__key">وقت الترحيل</span>
                        <span class="ac-je-meta-item__val">{{ $journalEntry->posted_at->format('Y/m/d H:i') }}</span>
                    </div>
                    @endif
                    <div class="ac-je-meta-item">
                        <span class="ac-je-meta-item__key">مُولَّد تلقائياً</span>
                        <span class="ac-je-meta-item__val">
                            @if($journalEntry->auto_generated)
                                <span class="ac-je-auto-badge">⚙️ نعم</span>
                            @else
                                <span class="ac-text-muted">يدوي</span>
                            @endif
                        </span>
                    </div>
                </div>

                {{-- Reversal links --}}
                @if($journalEntry->reversalOf)
                <div class="ac-je-reversal-link">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                    </svg>
                    هذا القيد يعكس:
                    <a href="{{ route('accounting.journal-entries.show', $journalEntry->reversalOf) }}"
                       class="ac-link">{{ $journalEntry->reversalOf->entry_number }}</a>
                </div>
                @endif
                @if($journalEntry->reversingEntry)
                <div class="ac-je-reversal-link ac-je-reversal-link--warn">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="15 10 20 15 15 20"/><path d="M4 4v7a4 4 0 0 0 4 4h12"/>
                    </svg>
                    تم عكسه بقيد:
                    <a href="{{ route('accounting.journal-entries.show', $journalEntry->reversingEntry) }}"
                       class="ac-link">{{ $journalEntry->reversingEntry->entry_number }}</a>
                </div>
                @endif

            </div>
        </div>

        {{-- ── Accounting breakdown ── --}}
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">التفصيل المحاسبي</p>

                <div class="ac-je-lines-wrap">
                    <table class="ac-table ac-je-lines-table">
                        <thead>
                            <tr>
                                <th>كود</th>
                                <th>اسم الحساب</th>
                                <th>البيان</th>
                                <th class="ac-text-end ac-je-col-dr">مدين</th>
                                <th class="ac-text-end ac-je-col-cr">دائن</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($journalEntry->lines as $line)
                            <tr>
                                <td class="ac-table__mono ac-je-line-code">{{ $line->account->code }}</td>
                                <td class="ac-je-line-name">{{ $line->account->name }}</td>
                                <td class="ac-je-line-desc ac-text-muted">{{ $line->description ?? '—' }}</td>
                                <td class="ac-text-end ac-table__mono">
                                    @if((float)$line->debit > 0)
                                        <span class="ac-je-debit">{{ number_format($line->debit, 2) }}</span>
                                    @else
                                        <span class="ac-text-muted">—</span>
                                    @endif
                                </td>
                                <td class="ac-text-end ac-table__mono">
                                    @if((float)$line->credit > 0)
                                        <span class="ac-je-credit">{{ number_format($line->credit, 2) }}</span>
                                    @else
                                        <span class="ac-text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="ac-je-totals-row">
                                <td colspan="3" class="ac-je-totals-row__label">الإجمالي</td>
                                <td class="ac-text-end ac-table__mono ac-je-debit">
                                    {{ number_format($totalDebit, 2) }}
                                </td>
                                <td class="ac-text-end ac-table__mono ac-je-credit">
                                    {{ number_format($totalCredit, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Balance status --}}
                <div class="ac-je-balance-status ac-je-balance-status--{{ $isBalanced ? 'ok' : 'error' }}">
                    @if($isBalanced)
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        القيد متوازن — مدين = دائن = {{ number_format($totalDebit, 2) }}
                    @else
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                        القيد غير متوازن — مدين: {{ number_format($totalDebit, 2) }} | دائن: {{ number_format($totalCredit, 2) }}
                    @endif
                </div>

            </div>
        </div>

    </div>{{-- .ac-je-show-main --}}

    {{-- ══ Sidebar ══════════════════════════════════════════════════════════════ --}}
    <div class="ac-je-show-sidebar">

        {{-- Source document --}}
        @if($source)
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">المستند المرتبط</p>
                <div class="ac-je-source-box">
                    <div class="ac-je-source-box__type">
                        {{ \App\Modules\Accounting\Models\JournalEntry::typeEmoji($refType) }}
                        {{ $source['label'] }}
                    </div>
                    <div class="ac-je-source-box__number ac-table__mono">
                        {{ $source['number'] }}
                    </div>
                    @if($source['url'])
                    <a href="{{ $source['url'] }}" class="ac-btn ac-btn--secondary ac-btn--sm ac-btn--full" style="margin-top:.75rem">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
                            <polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/>
                        </svg>
                        فتح المستند
                    </a>
                    @endif
                </div>
            </div>
        </div>
        @endif

        {{-- Quick summary --}}
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">ملخص سريع</p>
                <div class="ac-je-quick">
                    <div class="ac-je-quick__row">
                        <span>عدد البنود</span>
                        <span class="ac-table__mono">{{ $journalEntry->lines->count() }}</span>
                    </div>
                    <div class="ac-je-quick__row">
                        <span>إجمالي المدين</span>
                        <span class="ac-table__mono ac-je-debit">{{ number_format($totalDebit, 2) }}</span>
                    </div>
                    <div class="ac-je-quick__row">
                        <span>إجمالي الدائن</span>
                        <span class="ac-table__mono ac-je-credit">{{ number_format($totalCredit, 2) }}</span>
                    </div>
                    <div class="ac-je-quick__row ac-je-quick__row--balance">
                        <span>التوازن</span>
                        <span>
                            @if($isBalanced)
                                <span class="ac-je-balanced-ok">✔ متوازن</span>
                            @else
                                <span class="ac-je-balanced-err">✖ غير متوازن</span>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Immutability notice --}}
        <div class="ac-je-immutable-notice">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            هذا القيد <strong>غير قابل للتعديل</strong>.
            يُنشأ ويُعدَّل تلقائياً بواسطة النظام فقط.
        </div>

    </div>{{-- .ac-je-show-sidebar --}}

</div>{{-- .ac-je-show-layout --}}

@endsection
