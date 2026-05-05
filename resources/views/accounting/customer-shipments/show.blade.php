@extends('accounting._layout')

@section('title', 'مراجعة الشحنات')

@section('topbar-actions')
@can('can-write')
<a href="{{ route('accounting.customer-shipments.edit', $batch) }}" class="ac-btn ac-btn--primary">تعديل الشحنات</a>
@endcan
@endsection

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">مراجعة شحنات يوم {{ $batch->shipment_date->format('Y-m-d') }}</h1>
    <a href="{{ route('accounting.customer-shipments.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

<div class="ac-dash-grid" style="grid-template-columns:repeat(3,1fr)">
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الأشولة</span>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totals['quantity'], 2) }}</div>
        <div class="ac-dash-card__footer">تسليم {{ number_format($totals['delivery_quantity'], 2) }} / مرتجع {{ number_format($totals['return_quantity'], 2) }}</div>
    </div>
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المبيعات</span>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totals['sales'], 2) }}</div>
        <div class="ac-dash-card__footer">قيمة اليوم بالكامل</div>
    </div>
    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">عدد الشحنات</span>
        </div>
        <div class="ac-dash-card__amount">{{ $batch->entries->count() }}</div>
        <div class="ac-dash-card__footer">سطر عميل داخل هذا اليوم</div>
    </div>
</div>

<div class="ac-card ac-card--compact" style="margin-top:1rem">
    <div class="ac-card__body">
        <div class="ac-pl-side-stats">
            <div class="ac-pl-side-stat">
                <span class="ac-pl-side-stat__label">التاريخ</span>
                <span class="ac-pl-side-stat__val">{{ $batch->shipment_date->format('Y-m-d') }}</span>
            </div>
            <div class="ac-pl-side-stat">
                <span class="ac-pl-side-stat__label">ملاحظات اليوم</span>
                <span class="ac-pl-side-stat__val" style="white-space:normal">{{ $batch->notes ?: '—' }}</span>
            </div>
        </div>
    </div>
</div>

<div class="ac-card" style="margin-top:1rem">
    <div style="overflow-x:auto">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الكود</th>
                    <th>العميل</th>
                    <th>المحافظة</th>
                    <th>النوع</th>
                    <th>الأشولة</th>
                    <th>سعر الشوال</th>
                    <th>الإجمالي</th>
                    <th>مصدر السعر</th>
                    <th>ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($batch->entries as $entry)
                <tr>
                    <td><span class="ac-sh-code">{{ $entry->entry_code }}</span></td>
                    <td>{{ $entry->customer->name }}</td>
                    <td>{{ $entry->governorate->name_ar }}</td>
                    <td>
                        <span class="ac-badge {{ $entry->shipment_type === 'return' ? 'ac-badge--warning' : 'ac-badge--info' }}">
                            {{ $entry->shipment_type === 'return' ? 'مرتجع' : 'تسليم' }}
                        </span>
                    </td>
                    <td>{{ number_format((float) $entry->quantity, 2) }}</td>
                    <td>{{ number_format((float) $entry->unit_price, 2) }}</td>
                    <td>{{ number_format((float) $entry->line_total, 2) }}</td>
                    <td>{{ $entry->priceList?->name ?: '—' }}</td>
                    <td>{{ $entry->notes ?: '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
