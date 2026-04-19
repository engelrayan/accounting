@extends('accounting._layout')

@section('title', 'الأصول الثابتة')

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
@endphp

@section('topbar-actions')
    <a href="{{ route('accounting.assets.create') }}" class="ac-btn ac-btn--primary ac-btn--sm">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        إضافة أصل
    </a>
@endsection

@section('content')

@if($assets->isEmpty())
    <div class="ac-card">
        <div class="ac-empty-state">
            لا توجد أصول ثابتة بعد.
            <br>
            <a href="{{ route('accounting.assets.create') }}" class="ac-btn ac-btn--primary ac-mt-4">إضافة أول أصل</a>
        </div>
    </div>
@else

{{-- ── Summary cards ── --}}
<div class="ac-dash-grid">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد الأصول</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $assets->count() }}</div>
        <div class="ac-dash-card__footer">{{ $assets->where('status','active')->count() }} نشط</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي التكلفة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($assets->sum('purchase_cost'), 2) }}</div>
        <div class="ac-dash-card__footer">مجموع تكاليف الشراء</div>
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
        <div class="ac-dash-card__amount ac-text-danger">
            {{ number_format($assets->sum(fn($a) => $a->accumulatedDepreciation()), 2) }}
        </div>
        <div class="ac-dash-card__footer">إجمالي الإهلاك المسجل</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">القيمة الدفترية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="5" width="20" height="14" rx="2"/>
                    <line x1="2" y1="10" x2="22" y2="10"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">
            {{ number_format($assets->sum(fn($a) => $a->bookValue()), 2) }}
        </div>
        <div class="ac-dash-card__footer">القيمة الحالية الصافية</div>
    </div>
</div>

{{-- ── Table ── --}}
<div class="ac-table-wrap">
    <table class="ac-table">
        <thead>
            <tr>
                <th>الأصل</th>
                <th>الفئة</th>
                <th class="ac-col-num">التكلفة</th>
                <th class="ac-col-num">مجمع الإهلاك</th>
                <th class="ac-col-num">القيمة الحالية</th>
                <th>تقدم الإهلاك</th>
                <th>الحالة</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($assets as $asset)
            <tr>
                <td>
                    <div class="ac-font-bold">{{ $asset->name }}</div>
                    <div class="ac-text-sm ac-text-muted">{{ $asset->purchase_date->format('Y/m/d') }}</div>
                </td>
                <td class="ac-text-muted">{{ $categoryLabels[$asset->category] ?? $asset->category }}</td>
                <td class="ac-col-num">{{ number_format($asset->purchase_cost, 2) }}</td>
                <td class="ac-col-num ac-text-danger">{{ number_format($asset->accumulatedDepreciation(), 2) }}</td>
                <td class="ac-col-num ac-font-bold">{{ number_format($asset->bookValue(), 2) }}</td>
                <td>
                    <div class="ac-progress-wrap">
                        <div class="ac-progress-bar">
                            <div class="ac-progress-fill" data-pct="{{ $asset->depreciationProgress() }}"></div>
                        </div>
                        <span class="ac-text-sm ac-text-muted">{{ $asset->depreciationProgress() }}%</span>
                    </div>
                </td>
                <td>
                    @php $s = $statusLabels[$asset->status] ?? ['label' => $asset->status, 'badge' => 'ac-badge--draft']; @endphp
                    <span class="ac-badge {{ $s['badge'] }}">{{ $s['label'] }}</span>
                </td>
                <td>
                    <a href="{{ route('accounting.assets.show', $asset) }}"
                       class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endif
@endsection
