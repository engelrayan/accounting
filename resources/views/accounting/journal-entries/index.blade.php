@extends('accounting._layout')

@section('title', 'الحركات المحاسبية')

@section('content')

{{-- ════════════════════════════════════════════════════════════════════
     HEADER
     ════════════════════════════════════════════════════════════════════ --}}
<div class="ac-je-page-header">
    <div>
        <h1 class="ac-je-page-title">الحركات المحاسبية</h1>
        <p class="ac-je-page-sub">سجل تدقيق محاسبي كامل — يُنشأ تلقائياً بواسطة النظام</p>
    </div>
    <div class="ac-je-system-badge">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
        </svg>
        محمي — للقراءة فقط
    </div>
</div>

{{-- ════════════════════════════════════════════════════════════════════
     FILTERS
     ════════════════════════════════════════════════════════════════════ --}}
<form method="GET" action="{{ route('accounting.journal-entries.index') }}" class="ac-je-filters">
    <div class="ac-je-filters__grid">

        <div class="ac-form-group ac-form-group--sm">
            <label class="ac-label">من تاريخ</label>
            <input type="date" name="date_from" class="ac-control ac-control--sm"
                   value="{{ $dateFrom }}">
        </div>

        <div class="ac-form-group ac-form-group--sm">
            <label class="ac-label">إلى تاريخ</label>
            <input type="date" name="date_to" class="ac-control ac-control--sm"
                   value="{{ $dateTo }}">
        </div>

        <div class="ac-form-group ac-form-group--sm">
            <label class="ac-label">نوع الحركة</label>
            <select name="type" class="ac-control ac-control--sm">
                <option value="">— الكل —</option>
                @foreach($types as $t)
                <option value="{{ $t }}" {{ $type === $t ? 'selected' : '' }}>
                    {{ \App\Modules\Accounting\Models\JournalEntry::typeEmoji($t) }}
                    {{ \App\Modules\Accounting\Models\JournalEntry::typeLabel($t) }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="ac-form-group ac-form-group--sm">
            <label class="ac-label">الحساب</label>
            <select name="account_id" class="ac-control ac-control--sm">
                <option value="">— كل الحسابات —</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ (string)$accountId === (string)$acc->id ? 'selected' : '' }}>
                    {{ $acc->code }} — {{ $acc->name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="ac-form-group ac-form-group--sm">
            <label class="ac-label">الحالة</label>
            <select name="status" class="ac-control ac-control--sm">
                <option value="">— الكل —</option>
                <option value="posted"   {{ $status === 'posted'   ? 'selected' : '' }}>مُرحَّل</option>
                <option value="draft"    {{ $status === 'draft'    ? 'selected' : '' }}>مسوَّدة</option>
                <option value="reversed" {{ $status === 'reversed' ? 'selected' : '' }}>معكوس</option>
            </select>
        </div>

        <div class="ac-je-filters__actions">
            <button type="submit" class="ac-btn ac-btn--primary ac-btn--sm">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                بحث
            </button>
            <a href="{{ route('accounting.journal-entries.index') }}" class="ac-btn ac-btn--secondary ac-btn--sm">
                إعادة ضبط
            </a>
        </div>

    </div>
</form>

{{-- ════════════════════════════════════════════════════════════════════
     TABLE
     ════════════════════════════════════════════════════════════════════ --}}
<div class="ac-card">
    <div class="ac-card__body">

        @if($entries->isEmpty())
            <div class="ac-empty-state">
                <div class="ac-empty-state__icon">📒</div>
                <p class="ac-empty-state__text">لا توجد حركات محاسبية بالمعايير المحددة.</p>
            </div>
        @else

        <div class="ac-je-count">
            إجمالي النتائج: <strong>{{ $entries->total() }}</strong> حركة
        </div>

        <div class="ac-table-wrap">
            <table class="ac-table ac-je-table">
                <thead>
                    <tr>
                        <th>رقم القيد</th>
                        <th>النوع</th>
                        <th>الوصف</th>
                        <th>التاريخ</th>
                        <th class="ac-text-end">المبلغ</th>
                        <th>بواسطة</th>
                        <th>الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entries as $entry)
                    @php
                        $refType = $entry->reference_type;
                        $emoji   = \App\Modules\Accounting\Models\JournalEntry::typeEmoji($refType);
                        $label   = \App\Modules\Accounting\Models\JournalEntry::typeLabel($refType);
                        $typeMod = \App\Modules\Accounting\Models\JournalEntry::typeMod($refType);
                        $amount  = number_format((float) $entry->total_debit, 2);
                    @endphp
                    <tr class="{{ $entry->status === 'reversed' ? 'ac-je-row--reversed' : '' }}">
                        <td class="ac-table__mono ac-je-number">{{ $entry->entry_number }}</td>
                        <td>
                            <span class="ac-je-type-badge ac-je-type-badge--{{ $typeMod }}">
                                <span class="ac-je-type-badge__emoji">{{ $emoji }}</span>
                                <span class="ac-je-type-badge__label">{{ $label }}</span>
                            </span>
                        </td>
                        <td class="ac-je-desc">
                            <span class="ac-je-desc__text" title="{{ $entry->description }}">
                                {{ Str::limit($entry->description, 55) }}
                            </span>
                        </td>
                        <td class="ac-je-date">{{ $entry->entry_date->format('Y/m/d') }}</td>
                        <td class="ac-text-end ac-table__mono ac-je-amount">{{ $amount }}</td>
                        <td class="ac-je-creator">{{ $entry->creator?->name ?? 'النظام' }}</td>
                        <td>
                            @if($entry->status === 'posted')
                                <span class="ac-badge ac-badge--posted">مُرحَّل</span>
                            @elseif($entry->status === 'reversed')
                                <span class="ac-badge ac-badge--reversed">معكوس</span>
                            @else
                                <span class="ac-badge ac-badge--draft">مسوَّدة</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('accounting.journal-entries.show', $entry) }}"
                               class="ac-btn ac-btn--secondary ac-btn--xs">
                                عرض
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ac-pagination">
            {{ $entries->links() }}
        </div>

        @endif
    </div>
</div>

@endsection
