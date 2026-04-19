@extends('accounting._layout')

@section('title', 'كتالوج المنتجات والخدمات')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.products.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        منتج / خدمة جديد
    </a>
    @endcan
@endsection

@section('content')

@include('accounting._flash')

{{-- ── Summary Cards ─────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي العناصر</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $totalCount }}</div>
        <div class="ac-dash-card__footer">{{ $activeCount }} نشط</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">منتجات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                    <polyline points="3.27,6.96 12,12.01 20.73,6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $productCount }}</div>
        <div class="ac-dash-card__footer">منتج مادي</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">خدمات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 010 14.14M4.93 4.93a10 10 0 000 14.14"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $serviceCount }}</div>
        <div class="ac-dash-card__footer">خدمة أو عمل</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">معطّلة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ ($totalCount - $activeCount) > 0 ? 'ac-text-muted' : '' }}">
            {{ $totalCount - $activeCount }}
        </div>
        <div class="ac-dash-card__footer">عنصر غير نشط</div>
    </div>

</div>

{{-- ── Filter Bar ────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.products.index') }}" class="ac-filter-bar ac-prod-filter-form">
    <input type="text" name="q" value="{{ request('q') }}"
           class="ac-control ac-control--sm ac-prod-filter-form__search"
           placeholder="بحث بالاسم أو الرمز...">

    <select name="type" class="ac-select ac-select--sm">
        <option value="">كل الأنواع</option>
        <option value="product" {{ request('type') === 'product' ? 'selected' : '' }}>منتجات</option>
        <option value="service" {{ request('type') === 'service' ? 'selected' : '' }}>خدمات</option>
    </select>

    <select name="status" class="ac-select ac-select--sm">
        <option value="">كل الحالات</option>
        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>نشط</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّل</option>
    </select>

    <button type="submit" class="ac-btn ac-btn--secondary ac-btn--sm">تصفية</button>

    @if(request()->hasAny(['q','type','status']))
        <a href="{{ route('accounting.products.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">مسح</a>
    @endif
</form>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
@if($products->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد عناصر في الكتالوج</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                أضف منتجاتك وخدماتك لتُملأ الفواتير تلقائياً.
            </p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.products.create') }}" class="ac-btn ac-btn--primary">إضافة أول عنصر</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>الرمز</th>
                        <th>الاسم</th>
                        <th style="text-align:center">النوع</th>
                        <th>الوحدة</th>
                        <th style="text-align:left">سعر البيع</th>
                        <th style="text-align:left">سعر الشراء</th>
                        <th style="text-align:center">الضريبة %</th>
                        <th style="text-align:center">الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($products as $product)
                    <tr class="{{ !$product->is_active ? 'ac-table-row--muted' : '' }}">
                        <td style="font-variant-numeric:tabular-nums;color:var(--ac-text-muted);font-size:.82rem">
                            {{ $product->code ?? '—' }}
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $product->name }}</div>
                            @if($product->description)
                                <div style="font-size:.78rem;color:var(--ac-text-muted);max-width:260px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                    {{ $product->description }}
                                </div>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <span class="ac-prod-type-badge ac-prod-type-badge--{{ $product->type }}">
                                {{ $product->typeLabel() }}
                            </span>
                        </td>
                        <td style="color:var(--ac-text-muted)">{{ $product->unit ?? '—' }}</td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums">
                            {{ number_format($product->sale_price, 2) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-text-muted)">
                            {{ $product->purchase_price !== null ? number_format($product->purchase_price, 2) : '—' }}
                        </td>
                        <td style="text-align:center;font-variant-numeric:tabular-nums">
                            @if($product->tax_rate > 0)
                                <span class="ac-badge">{{ number_format($product->tax_rate, 2) }}%</span>
                            @else
                                <span style="color:var(--ac-text-muted)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($product->is_active)
                                <span class="ac-badge ac-badge--success">نشط</span>
                            @else
                                <span class="ac-badge ac-badge--muted">معطّل</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                @can('can-write')
                                <a href="{{ route('accounting.products.edit', $product) }}"
                                   class="ac-btn ac-btn--secondary ac-btn--sm">تعديل</a>

                                <form method="POST"
                                      action="{{ route('accounting.products.toggle', $product) }}"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit"
                                            class="ac-btn ac-btn--ghost ac-btn--sm"
                                            title="{{ $product->is_active ? 'تعطيل' : 'تفعيل' }}">
                                        {{ $product->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
