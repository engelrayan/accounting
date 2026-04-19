@extends('accounting._layout')

@section('title', 'المخزون')

@section('topbar-actions')
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounting.inventory.movements') }}" class="ac-btn ac-btn--secondary">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="17,1 21,5 17,9"/><path d="M3 11V9a4 4 0 014-4h14"/>
                <polyline points="7,23 3,19 7,15"/><path d="M21 13v2a4 4 0 01-4 4H3"/>
            </svg>
            سجل الحركات
        </a>
    </div>
@endsection

@section('content')

@include('accounting._flash')

{{-- ── بطاقات الملخص ──────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الأصناف</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalItems) }}</div>
        <div class="ac-dash-card__footer">صنف مرصود</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي قيمة المخزون</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalValue, 2) }}</div>
        <div class="ac-dash-card__footer">بالتكلفة المتوسطة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">مخزون منخفض</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $lowStockCount > 0 ? 'ac-text-warning' : '' }}">{{ $lowStockCount }}</div>
        <div class="ac-dash-card__footer">أصناف دون حد إعادة الطلب</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">مخزون صفري</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount {{ $zeroStockCount > 0 ? 'ac-text-danger' : '' }}">{{ $zeroStockCount }}</div>
        <div class="ac-dash-card__footer">أصناف بدون رصيد</div>
    </div>

</div>

{{-- ── فلتر البحث ──────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.inventory.index') }}" class="ac-filter-bar">
    <input type="text" name="q" value="{{ request('q') }}"
           class="ac-control ac-control--sm"
           placeholder="بحث بالاسم أو الرمز...">

    <select name="status" class="ac-select ac-select--sm">
        <option value="">كل الأصناف</option>
        <option value="low"  {{ request('status') === 'low'  ? 'selected' : '' }}>مخزون منخفض</option>
        <option value="zero" {{ request('status') === 'zero' ? 'selected' : '' }}>مخزون صفري</option>
    </select>

    <button type="submit" class="ac-btn ac-btn--secondary ac-btn--sm">تصفية</button>

    @if(request()->hasAny(['q','status']))
        <a href="{{ route('accounting.inventory.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">مسح</a>
    @endif

    <span style="margin-right:auto;font-size:.82rem;color:var(--ac-text-muted)">
        المستودع: {{ $warehouse->name }}
    </span>
</form>

{{-- ── الجدول ──────────────────────────────────────────────────────────── --}}
@if($items->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد حركات مخزون حتى الآن</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                أصناف المنتجات ستظهر هنا بعد أول فاتورة شراء.
            </p>
        </div>
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>الرمز</th>
                        <th>الصنف</th>
                        <th>الوحدة</th>
                        <th style="text-align:left">الكمية المتاحة</th>
                        <th style="text-align:left">متوسط التكلفة</th>
                        <th style="text-align:left">القيمة الإجمالية</th>
                        <th style="text-align:center">الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    @php
                        $qty        = (float) $item->quantity_on_hand;
                        $avg        = (float) $item->average_cost;
                        $value      = round($qty * $avg, 2);
                        $isLow      = $item->reorder_level && $qty <= (float)$item->reorder_level;
                        $isZero     = $qty <= 0;
                    @endphp
                    <tr>
                        <td style="font-variant-numeric:tabular-nums;color:var(--ac-text-muted);font-size:.82rem">
                            {{ $item->product->code ?? '—' }}
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $item->product->name ?? '—' }}</div>
                            @if(!$item->product?->is_active)
                                <div style="font-size:.75rem;color:var(--ac-text-muted)">معطّل</div>
                            @endif
                        </td>
                        <td style="color:var(--ac-text-muted)">{{ $item->product->unit ?? '—' }}</td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600;
                                   {{ $isZero ? 'color:var(--ac-danger)' : ($isLow ? 'color:var(--ac-warning)' : '') }}">
                            {{ number_format($qty, 3) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-text-muted)">
                            {{ number_format($avg, 4) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600">
                            {{ number_format($value, 2) }}
                        </td>
                        <td style="text-align:center">
                            @if($isZero)
                                <span class="ac-badge ac-badge--danger">صفري</span>
                            @elseif($isLow)
                                <span class="ac-badge ac-badge--warning">منخفض</span>
                            @else
                                <span class="ac-badge ac-badge--success">متاح</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                <a href="{{ route('accounting.inventory.show', $item->product) }}"
                                   class="ac-btn ac-btn--secondary ac-btn--sm">التفاصيل</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px">
        {{ $items->links() }}
    </div>
@endif

@endsection
