@extends('accounting._layout')

@section('title', $priceList->name)

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.price-lists.edit', $priceList) }}" class="ac-btn ac-btn--primary">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
        </svg>
        تعديل
    </a>
    @endcan
@endsection

@section('content')

@include('accounting._flash')

<div class="ac-page-header">
    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
        <h1 class="ac-page-header__title">{{ $priceList->name }}</h1>
        @if($priceList->is_default)
            <span class="ac-pl-default-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                افتراضية
            </span>
        @endif
        @if($priceList->is_active)
            <span class="ac-badge ac-badge--success">نشطة</span>
        @else
            <span class="ac-badge ac-badge--muted">معطّلة</span>
        @endif
    </div>
    <a href="{{ route('accounting.price-lists.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<div class="ac-pl-layout">

    {{-- ── Main ───────────────────────────────────────────────────────────── --}}
    <div class="ac-pl-main">

        @if($priceList->description)
        <div class="ac-card ac-card--compact">
            <div class="ac-card__body" style="padding:.75rem 1rem">
                <p style="margin:0;font-size:.88rem;color:var(--ac-text-muted)">{{ $priceList->description }}</p>
            </div>
        </div>
        @endif

        {{-- Governorates table --}}
        <div class="ac-card ac-card--compact">
            <div class="ac-card__body">
                <p class="ac-section-label">المحافظات والأسعار</p>

                @if(empty($itemsMap))
                    <p style="color:var(--ac-text-muted);font-size:.88rem;padding:.5rem 0">لم يتم تسعير أي محافظة بعد.</p>
                @else
                    <div class="ac-pl-tbl-wrap">
                        <table class="ac-pl-tbl ac-pl-tbl--show">
                            <thead>
                                <tr>
                                    <th>المحافظة</th>
                                    <th>
                                        <span class="ac-pl-th-badge ac-pl-th-badge--delivery">سعر التسليم</span>
                                    </th>
                                    <th>
                                        <span class="ac-pl-th-badge ac-pl-th-badge--return">سعر المرتجع</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($governorates as $gov)
                                    @if(!isset($itemsMap[$gov->id])) @continue @endif
                                <tr>
                                    <td class="ac-pl-tbl__name" style="font-weight:600">{{ $gov->name_ar }}</td>
                                    <td>
                                        <span class="ac-pl-show-price ac-pl-show-price--delivery">
                                            {{ number_format($itemsMap[$gov->id], 2) }}
                                            <span class="ac-pl-show-currency">ج.م</span>
                                        </span>
                                    </td>
                                    <td>
                                        @if(isset($returnPricesMap[$gov->id]) && $returnPricesMap[$gov->id] !== null)
                                            <span class="ac-pl-show-price ac-pl-show-price--return">
                                                {{ number_format($returnPricesMap[$gov->id], 2) }}
                                                <span class="ac-pl-show-currency">ج.م</span>
                                            </span>
                                        @else
                                            <span style="color:var(--ac-text-muted);font-size:.82rem">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Customers --}}
        @if($priceList->customers->isNotEmpty())
        <div class="ac-card ac-card--compact">
            <div class="ac-card__body">
                <p class="ac-section-label">العملاء المرتبطون</p>
                <div class="ac-pl-cust-tags">
                    @foreach($priceList->customers as $cust)
                    <a href="{{ route('accounting.customers.show', $cust) }}"
                       class="ac-pl-cust-tag">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        {{ $cust->name }}
                    </a>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

    </div>

    {{-- ── Side ────────────────────────────────────────────────────────────── --}}
    <div class="ac-pl-side">
        <div class="ac-card">
            <div class="ac-card__body">
                <p class="ac-section-label">ملخص</p>

                <div class="ac-pl-side-stats">
                    <div class="ac-pl-side-stat">
                        <span class="ac-pl-side-stat__label">محافظات مُسعَّرة</span>
                        <span class="ac-pl-side-stat__val">{{ count($itemsMap) }}</span>
                    </div>
                    @php
                        $withReturn = collect($returnPricesMap)->filter(fn($v) => $v !== null)->count();
                    @endphp
                    @if($withReturn > 0)
                    <div class="ac-pl-side-stat">
                        <span class="ac-pl-side-stat__label">بسعر مرتجع</span>
                        <span class="ac-pl-side-stat__val">{{ $withReturn }}</span>
                    </div>
                    @endif
                    @if(count($itemsMap) > 0)
                    <div class="ac-pl-side-stat">
                        <span class="ac-pl-side-stat__label">متوسط التسليم</span>
                        <span class="ac-pl-side-stat__val" style="font-size:.82rem">
                            {{ number_format(array_sum($itemsMap) / count($itemsMap), 2) }} ج.م
                        </span>
                    </div>
                    @endif
                    <div class="ac-pl-side-stat">
                        <span class="ac-pl-side-stat__label">عملاء مرتبطون</span>
                        <span class="ac-pl-side-stat__val">{{ $priceList->customers->count() }}</span>
                    </div>
                    <div class="ac-pl-side-stat">
                        <span class="ac-pl-side-stat__label">آخر تحديث</span>
                        <span class="ac-pl-side-stat__val" style="font-size:.8rem">
                            {{ $priceList->updated_at->format('Y-m-d') }}
                        </span>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:.6rem;margin-top:1.25rem">
                    @can('can-write')
                    <a href="{{ route('accounting.price-lists.edit', $priceList) }}"
                       class="ac-btn ac-btn--primary ac-btn--full">تعديل</a>

                    <form method="POST"
                          action="{{ route('accounting.price-lists.set-default', $priceList) }}">
                        @csrf
                        <button type="submit"
                                class="ac-btn ac-btn--full {{ $priceList->is_default ? 'ac-btn--warning-ghost' : 'ac-btn--ghost' }}">
                            @if($priceList->is_default)
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor" style="vertical-align:middle">
                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
                                </svg>
                                إلغاء الافتراضية
                            @else
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle">
                                    <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
                                </svg>
                                تعيين كافتراضية
                            @endif
                        </button>
                    </form>

                    @if(!$priceList->is_default)
                    <form method="POST"
                          action="{{ route('accounting.price-lists.toggle', $priceList) }}">
                        @csrf
                        <button type="submit"
                                class="ac-btn ac-btn--full {{ $priceList->is_active ? 'ac-btn--ghost' : 'ac-btn--ghost ac-btn--success-ghost' }}">
                            {{ $priceList->is_active ? 'تعطيل القائمة' : 'تفعيل القائمة' }}
                        </button>
                    </form>
                    @endif
                    @endcan

                    <a href="{{ route('accounting.price-lists.index') }}"
                       class="ac-btn ac-btn--secondary ac-btn--full">رجوع</a>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
