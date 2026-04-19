@extends('accounting._layout')

@section('title', 'سجل حركات المخزون')

@section('topbar-actions')
    <a href="{{ route('accounting.inventory.index') }}" class="ac-btn ac-btn--secondary">رجوع للمخزون</a>
@endsection

@section('content')

@include('accounting._flash')

{{-- ── فلتر ─────────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.inventory.movements') }}" class="ac-filter-bar">

    <select name="product_id" class="ac-select ac-select--sm" style="min-width:200px">
        <option value="">كل الأصناف</option>
        @foreach($products as $p)
            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>
                {{ $p->name }}{{ $p->code ? ' ('.$p->code.')' : '' }}
            </option>
        @endforeach
    </select>

    <select name="type" class="ac-select ac-select--sm">
        <option value="">كل الأنواع</option>
        <option value="purchase"   {{ request('type') === 'purchase'   ? 'selected' : '' }}>شراء</option>
        <option value="sale"       {{ request('type') === 'sale'       ? 'selected' : '' }}>بيع</option>
        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>تسوية</option>
        <option value="return"     {{ request('type') === 'return'     ? 'selected' : '' }}>مرتجع</option>
    </select>

    <input type="date" name="from" value="{{ request('from') }}"
           class="ac-control ac-control--sm" placeholder="من تاريخ">

    <input type="date" name="to" value="{{ request('to') }}"
           class="ac-control ac-control--sm" placeholder="إلى تاريخ">

    <button type="submit" class="ac-btn ac-btn--secondary ac-btn--sm">تصفية</button>

    @if(request()->hasAny(['product_id','type','from','to']))
        <a href="{{ route('accounting.inventory.movements') }}" class="ac-btn ac-btn--ghost ac-btn--sm">مسح</a>
    @endif
</form>

{{-- ── الجدول ──────────────────────────────────────────────────────────── --}}
@if($movements->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <polyline points="17,1 21,5 17,9"/><path d="M3 11V9a4 4 0 014-4h14"/>
            <polyline points="7,23 3,19 7,15"/><path d="M21 13v2a4 4 0 01-4 4H3"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد حركات مطابقة للفلتر</p>
        </div>
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>الصنف</th>
                        <th style="text-align:center">النوع</th>
                        <th style="text-align:left">الكمية</th>
                        <th style="text-align:left">تكلفة الوحدة</th>
                        <th style="text-align:left">الإجمالي</th>
                        <th>مرجع</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($movements as $mov)
                    <tr>
                        <td style="white-space:nowrap;color:var(--ac-text-muted);font-size:.82rem">
                            {{ $mov->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td>
                            @if($mov->product)
                                <div style="font-weight:600">{{ $mov->product->name }}</div>
                                @if($mov->product->code)
                                    <div style="font-size:.75rem;color:var(--ac-text-muted)">{{ $mov->product->code }}</div>
                                @endif
                            @else
                                <span style="color:var(--ac-text-muted)">—</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <span class="ac-badge {{ $mov->typeBadgeClass() }}">{{ $mov->typeLabel() }}</span>
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600;
                                   color:{{ (float)$mov->quantity >= 0 ? 'var(--ac-success)' : 'var(--ac-danger)' }}">
                            {{ (float)$mov->quantity >= 0 ? '+' : '' }}{{ number_format((float)$mov->quantity, 3) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-text-muted)">
                            {{ number_format((float)$mov->unit_cost, 4) }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600">
                            {{ number_format((float)$mov->total_cost, 2) }}
                        </td>
                        <td style="font-size:.82rem;color:var(--ac-text-muted)">
                            @if($mov->reference_type && $mov->reference_id)
                                {{ $mov->reference_type }} #{{ $mov->reference_id }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:.82rem;color:var(--ac-text-muted);max-width:160px;
                                   white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            {{ $mov->notes ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:16px">
        {{ $movements->links() }}
    </div>
@endif

@endsection
