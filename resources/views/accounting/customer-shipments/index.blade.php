@extends('accounting._layout')

@section('title', 'شحنات العملاء')

@section('topbar-actions')
@can('can-write')
<a href="{{ route('accounting.customer-shipments.create') }}" class="ac-btn ac-btn--primary">شحنات جديدة</a>
@endcan
@endsection

@section('content')
@if($batches->isEmpty())
    <div class="ac-empty">
        <div>
            <p style="font-weight:700;margin:0">لا توجد شحنات مسجلة</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">ابدأ بإدخال شحنات اليوم ثم راجع الأكواد والإجماليات بعد الحفظ.</p>
        </div>
        <a href="{{ route('accounting.customer-shipments.create') }}" class="ac-btn ac-btn--primary">إدخال شحنات</a>
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>عدد الشحنات</th>
                        <th>إجمالي الأشولة</th>
                        <th>إجمالي القيمة</th>
                        <th>أضيفت بواسطة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($batches as $batch)
                    <tr>
                        <td>{{ $batch->shipment_date->format('Y-m-d') }}</td>
                        <td>{{ $batch->entries_count }}</td>
                        <td>{{ number_format((float) $batch->total_quantity, 2) }}</td>
                        <td>{{ number_format((float) $batch->total_sales, 2) }}</td>
                        <td>{{ $batch->creator?->name ?: '—' }}</td>
                        <td>
                            <div style="display:flex;justify-content:flex-end;gap:.4rem">
                                <a href="{{ route('accounting.customer-shipments.show', $batch) }}" class="ac-btn ac-btn--secondary ac-btn--sm">مراجعة</a>
                                @can('can-write')
                                <a href="{{ route('accounting.customer-shipments.edit', $batch) }}" class="ac-btn ac-btn--ghost ac-btn--sm">تعديل</a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div style="margin-top:1rem">
        {{ $batches->links() }}
    </div>
@endif
@endsection
