@extends('accounting._layout')

@section('title', 'قوائم الأسعار')

@section('topbar-actions')
    @can('can-write')
    <a href="{{ route('accounting.price-lists.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        قائمة أسعار جديدة
    </a>
    @if(companyModuleEnabled('customer_shipments'))
    <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--secondary">
        شحنات العملاء
    </a>
    @endif
    @endcan
@endsection

@section('content')

@include('accounting._flash')

{{-- ── Default list notice ─────────────────────────────────────────────── --}}
@php $hasDefault = $priceLists->firstWhere('is_default', true); @endphp
@if(!$hasDefault && $priceLists->isNotEmpty())
<div class="ac-alert ac-alert--warning" style="display:flex;align-items:center;gap:.6rem">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0">
        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
    </svg>
    <span>لا توجد قائمة أسعار افتراضية. قم بتعيين قائمة كافتراضية حتى تُطبَّق تلقائياً على العملاء غير المرتبطين بقائمة محددة.</span>
</div>
@endif

{{-- ── Summary Cards ──────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid" style="grid-template-columns: repeat(3, 1fr)">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي القوائم</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <line x1="9" y1="12" x2="15" y2="12"/>
                    <line x1="9" y1="16" x2="13" y2="16"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $totalCount }}</div>
        <div class="ac-dash-card__footer">قائمة مسجّلة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">نشطة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount ac-text-success">{{ $activeCount }}</div>
        <div class="ac-dash-card__footer">قائمة نشطة</div>
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
        <div class="ac-dash-card__footer">قائمة غير نشطة</div>
    </div>

</div>

{{-- ── Filter Bar ────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.price-lists.index') }}"
      class="ac-filter-bar ac-prod-filter-form">
    <input type="text" name="q" value="{{ request('q') }}"
           class="ac-control ac-control--sm ac-prod-filter-form__search"
           placeholder="بحث بالاسم...">

    <select name="status" class="ac-select ac-select--sm">
        <option value="">كل الحالات</option>
        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>نشطة</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّلة</option>
    </select>

    <button type="submit" class="ac-btn ac-btn--secondary ac-btn--sm">تصفية</button>

    @if(request()->hasAny(['q','status']))
        <a href="{{ route('accounting.price-lists.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">مسح</a>
    @endif

    <div style="margin-right:auto">
        @if(companyModuleEnabled('customer_shipments'))
        <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 7h13"/><path d="M3 12h10"/><path d="M3 17h8"/><circle cx="18" cy="17" r="3"/>
            </svg>
            شحنات العملاء
        </a>
        @endif
        <a href="{{ route('accounting.governorates.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s-8-4.5-8-11.8A8 8 0 0112 2a8 8 0 018 8.2c0 7.3-8 11.8-8 11.8z"/>
                <circle cx="12" cy="10" r="3"/>
            </svg>
            إدارة المحافظات
        </a>
    </div>
</form>

{{-- ── Table ────────────────────────────────────────────────────────────── --}}
@if($priceLists->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا توجد قوائم أسعار</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">
                أنشئ أول قائمة أسعار لتحديد تسعيرة المحافظات.
            </p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.price-lists.create') }}" class="ac-btn ac-btn--primary">إنشاء قائمة أسعار</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>الاسم</th>
                        <th>الوصف</th>
                        <th style="text-align:center">محافظات</th>
                        <th style="text-align:center">عملاء</th>
                        <th style="text-align:center">الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($priceLists as $pl)
                    <tr class="{{ !$pl->is_active ? 'ac-table-row--muted' : '' }}">
                        <td>
                            <div style="display:flex;align-items:center;gap:.5rem">
                                <a href="{{ route('accounting.price-lists.show', $pl) }}"
                                   class="ac-table-link" style="font-weight:600">
                                    {{ $pl->name }}
                                </a>
                                @if($pl->is_default)
                                    <span class="ac-pl-default-badge">
                                        <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor">
                                            <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/>
                                        </svg>
                                        افتراضية
                                    </span>
                                @endif
                            </div>
                            @if($pl->description)
                            <div style="font-size:.78rem;color:var(--ac-text-muted);margin-top:2px;
                                        max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $pl->description }}
                            </div>
                            @endif
                        </td>
                        <td style="color:var(--ac-text-muted);font-size:.83rem">
                            {{ $pl->updated_at->format('Y-m-d') }}
                        </td>
                        <td style="text-align:center">
                            <span class="ac-badge ac-badge--info">{{ $pl->items_count }}</span>
                        </td>
                        <td style="text-align:center">
                            <span class="ac-badge {{ $pl->customers_count > 0 ? 'ac-badge--success' : '' }}">
                                {{ $pl->customers_count }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            @if($pl->is_active)
                                <span class="ac-badge ac-badge--success">نشطة</span>
                            @else
                                <span class="ac-badge ac-badge--muted">معطّلة</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:5px;justify-content:flex-end;flex-wrap:wrap">
                                <a href="{{ route('accounting.price-lists.show', $pl) }}"
                                   class="ac-btn ac-btn--secondary ac-btn--sm">عرض</a>

                                @can('can-write')
                                <a href="{{ route('accounting.price-lists.edit', $pl) }}"
                                   class="ac-btn ac-btn--ghost ac-btn--sm">تعديل</a>

                                <form method="POST"
                                      action="{{ route('accounting.price-lists.set-default', $pl) }}"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit"
                                            class="ac-btn ac-btn--sm {{ $pl->is_default ? 'ac-btn--warning-ghost' : 'ac-btn--ghost' }}"
                                            title="{{ $pl->is_default ? 'إلغاء الافتراضية' : 'تعيين كافتراضية' }}">
                                        @if($pl->is_default)
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                                        @else
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
                                        @endif
                                        {{ $pl->is_default ? 'افتراضية' : 'تعيين' }}
                                    </button>
                                </form>

                                @if(!$pl->is_default)
                                <form method="POST"
                                      action="{{ route('accounting.price-lists.toggle', $pl) }}"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                                        {{ $pl->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                                @endif
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
