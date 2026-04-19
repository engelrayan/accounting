@extends('accounting._layout')

@section('title', $asset->name)

@php
$statusLabels = [
    'active'            => ['label' => 'نشط',          'badge' => 'ac-badge--asset-active'],
    'fully_depreciated' => ['label' => 'مهلك بالكامل', 'badge' => 'ac-badge--asset-fully_depreciated'],
    'disposed'          => ['label' => 'مستغنى عنه',   'badge' => 'ac-badge--asset-disposed'],
];
$categoryLabels = [
    'vehicle'   => 'سيارة',
    'equipment' => 'معدات',
    'furniture' => 'أثاث',
    'building'  => 'مبنى',
    'other'     => 'أخرى',
];
$s = $statusLabels[$asset->status] ?? ['label' => $asset->status, 'badge' => 'ac-badge--draft'];
@endphp

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">{{ $asset->name }}</h1>
    <div class="ac-page-header__actions">
        @if($asset->isActive() && !$asset->isFullyDepreciated())
            <form method="POST"
                  action="{{ route('accounting.assets.depreciate', $asset) }}"
                  class="ac-inline-form">
                @csrf
                <button type="submit" class="ac-btn ac-btn--primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                        <polyline points="17,18 23,18 23,12"/>
                    </svg>
                    تسجيل إهلاك شهري
                </button>
            </form>
        @endif
        <a href="{{ route('accounting.assets.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
</div>

@if($errors->has('depreciate'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('depreciate') }}</div>
@endif

{{-- ── Summary cards ── --}}
<div class="ac-dash-grid">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">تكلفة الشراء</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($asset->purchase_cost, 2) }}</div>
        <div class="ac-dash-card__footer">القيمة التخريدية: {{ number_format($asset->salvage_value, 2) }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الإهلاك الشهري</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($asset->monthlyDepreciation(), 2) }}</div>
        <div class="ac-dash-card__footer">العمر الافتراضي: {{ $asset->useful_life }} شهر</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">مجمع الإهلاك</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                    <polyline points="17,18 23,18 23,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-danger">{{ number_format($asset->accumulatedDepreciation(), 2) }}</div>
        <div class="ac-dash-card__footer">{{ $asset->depreciated_months }} شهر من {{ $asset->useful_life }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">القيمة الحالية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($asset->bookValue(), 2) }}</div>
        <div class="ac-dash-card__footer">المتبقي: {{ $asset->remainingMonths() }} شهر</div>
    </div>
</div>

{{-- ── Asset meta + progress ── --}}
<div class="ac-card ac-mt-4">
    <div class="ac-entry-meta">
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">الفئة</div>
            <div class="ac-entry-meta__value">{{ $categoryLabels[$asset->category] ?? $asset->category }}</div>
        </div>
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">تاريخ الشراء</div>
            <div class="ac-entry-meta__value">{{ $asset->purchase_date->format('Y/m/d') }}</div>
        </div>
        <div class="ac-entry-meta__item">
            <div class="ac-entry-meta__label">الحالة</div>
            <div class="ac-entry-meta__value">
                <span class="ac-badge {{ $s['badge'] }}">{{ $s['label'] }}</span>
            </div>
        </div>
    </div>

    <div class="ac-card__body">
        {{-- Progress bar --}}
        <div class="ac-progress-section">
            <div class="ac-progress-header">
                <span>تقدم الإهلاك</span>
                <span>{{ $asset->depreciated_months }} / {{ $asset->useful_life }} شهر ({{ $asset->depreciationProgress() }}%)</span>
            </div>
            <div class="ac-progress-bar ac-progress-bar--lg">
                <div class="ac-progress-fill" data-pct="{{ $asset->depreciationProgress() }}"></div>
            </div>
        </div>

        <hr class="ac-divider">

        {{-- Linked accounts --}}
        <p class="ac-section-label">الحسابات المرتبطة</p>
        <div class="ac-asset-accounts__grid">
            <div>
                <span class="ac-text-muted">حساب الأصل: </span>
                <span class="ac-text-mono">{{ $asset->account->code }}</span>
                {{ $asset->account->name }}
            </div>
            <div>
                <span class="ac-text-muted">مجمع الإهلاك: </span>
                <span class="ac-text-mono">{{ $asset->accumulatedDepreciationAccount->code }}</span>
                {{ $asset->accumulatedDepreciationAccount->name }}
            </div>
            <div>
                <span class="ac-text-muted">مصروف الإهلاك: </span>
                <span class="ac-text-mono">{{ $asset->depreciationExpenseAccount->code }}</span>
                {{ $asset->depreciationExpenseAccount->name }}
            </div>
            <div>
                <span class="ac-text-muted">حساب الدفع: </span>
                <span class="ac-text-mono">{{ $asset->paymentAccount->code }}</span>
                {{ $asset->paymentAccount->name }}
            </div>
        </div>

        @if($asset->notes)
            <hr class="ac-divider">
            <p class="ac-section-label">ملاحظات</p>
            <p class="ac-text-sm">{{ $asset->notes }}</p>
        @endif
    </div>
</div>

{{-- ── Depreciation history ── --}}
<h2 class="ac-section-title ac-mt-4">سجل قيود الإهلاك</h2>

<div class="ac-table-wrap">
    @if($depreciationEntries->isEmpty())
        <div class="ac-empty-state ac-empty-state--sm ac-text-muted">
            لم يُسجَّل إهلاك بعد.
        </div>
    @else
        <table class="ac-table">
            <thead>
                <tr>
                    <th>رقم القيد</th>
                    <th>التاريخ</th>
                    <th>الوصف</th>
                    <th class="ac-col-num">المبلغ</th>
                    <th>الحالة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($depreciationEntries as $entry)
                <tr>
                    <td class="ac-text-mono">{{ $entry->entry_number }}</td>
                    <td>{{ $entry->entry_date->format('Y/m/d') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="ac-col-num">{{ number_format($entry->lines->sum('debit'), 2) }}</td>
                    <td>
                        <span class="ac-badge ac-badge--{{ $entry->status }}">
                            @if($entry->status === 'posted') مُرحَّل
                            @elseif($entry->status === 'draft') مسودة
                            @else مُعكوس @endif
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('accounting.journal-entries.show', $entry) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
