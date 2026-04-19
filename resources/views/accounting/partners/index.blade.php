@extends('accounting._layout')

@section('title', 'الشركاء')

@section('content')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">الشركاء</h1>
    <div class="ac-page-header__actions">
        <a href="{{ route('accounting.partners.create') }}" class="ac-btn ac-btn--primary">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            إضافة شريك
        </a>
    </div>
</div>

@if($partnerData->isEmpty())
    <div class="ac-card">
        <div class="ac-empty-state">
            <p>لا يوجد شركاء بعد.</p>
            <a href="{{ route('accounting.partners.create') }}" class="ac-btn ac-btn--primary ac-mt-4">إضافة أول شريك</a>
        </div>
    </div>
@else

    {{-- Summary cards --}}
    <div class="ac-dash-grid">
        <div class="ac-dash-card">
            <div class="ac-dash-card__header">
                <span class="ac-dash-card__label">عدد الشركاء</span>
                <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                </div>
            </div>
            <div class="ac-dash-card__amount">{{ $partnerData->count() }}</div>
            <div class="ac-dash-card__footer">شريك نشط</div>
        </div>

        <div class="ac-dash-card">
            <div class="ac-dash-card__header">
                <span class="ac-dash-card__label">إجمالي رأس المال</span>
                <div class="ac-dash-card__icon ac-dash-card__icon--green">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
            </div>
            <div class="ac-dash-card__amount">{{ number_format($partnerData->sum('capital'), 2) }}</div>
            <div class="ac-dash-card__footer">مجموع مساهمات الشركاء</div>
        </div>

        <div class="ac-dash-card">
            <div class="ac-dash-card__header">
                <span class="ac-dash-card__label">إجمالي المسحوبات</span>
                <div class="ac-dash-card__icon ac-dash-card__icon--red">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="23,18 13.5,8.5 8.5,13.5 1,6"/>
                        <polyline points="17,18 23,18 23,12"/>
                    </svg>
                </div>
            </div>
            <div class="ac-dash-card__amount">{{ number_format($partnerData->sum('drawings'), 2) }}</div>
            <div class="ac-dash-card__footer">مجموع المسحوبات</div>
        </div>

        <div class="ac-dash-card">
            <div class="ac-dash-card__header">
                <span class="ac-dash-card__label">صافي الأرصدة</span>
                <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="5" width="20" height="14" rx="2"/>
                        <line x1="2" y1="10" x2="22" y2="10"/>
                    </svg>
                </div>
            </div>
            <div class="ac-dash-card__amount">{{ number_format($partnerData->sum('balance'), 2) }}</div>
            <div class="ac-dash-card__footer">رأس المال بعد خصم المسحوبات</div>
        </div>
    </div>

    {{-- Partners table --}}
    <div class="ac-table-wrap">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الشريك</th>
                    <th>رأس المال</th>
                    <th>المسحوبات</th>
                    <th>صافي الرصيد</th>
                    <th>نسبة الشراكة</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($partnerData as $row)
                <tr>
                    <td>
                        <div class="ac-font-bold">{{ $row['partner']->name }}</div>
                        @if($row['partner']->phone)
                            <div class="ac-text-sm ac-text-muted">{{ $row['partner']->phone }}</div>
                        @endif
                    </td>
                    <td class="ac-col-num">{{ number_format($row['capital'], 2) }}</td>
                    <td class="ac-col-num ac-text-danger">{{ number_format($row['drawings'], 2) }}</td>
                    <td class="ac-col-num ac-font-bold">{{ number_format($row['balance'], 2) }}</td>
                    <td>
                        <div class="ac-progress-wrap">
                            <div class="ac-progress-bar">
                                <div class="ac-progress-fill" data-pct="{{ $row['percentage'] }}"></div>
                            </div>
                            <span class="ac-text-sm ac-text-muted">{{ $row['percentage'] }}%</span>
                        </div>
                    </td>
                    <td>
                        <a href="{{ route('accounting.partners.show', $row['partner']) }}"
                           class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endif
@endsection
