@extends('accounting._layout')
@section('title', 'قيد متكرر: ' . $recurringEntry->description)

@section('topbar-actions')
<div style="display:flex;gap:.75rem;align-items:center;">
    <span class="ac-badge {{ $recurringEntry->is_active ? 'ac-badge--success' : 'ac-badge--muted' }}" style="font-size:.9rem;padding:.4rem .9rem;">
        {{ $recurringEntry->is_active ? 'نشط' : 'موقوف' }}
    </span>
    <form method="POST" action="{{ route('accounting.recurring.toggle', $recurringEntry) }}">
        @csrf
        <button type="submit" class="ac-btn {{ $recurringEntry->is_active ? 'ac-btn--secondary' : 'ac-btn--success' }}">
            {{ $recurringEntry->is_active ? 'إيقاف' : 'تفعيل' }}
        </button>
    </form>
</div>
@endsection

@section('content')

<div class="ac-grid-2" style="gap:1.5rem;">

    {{-- Info Card --}}
    <div class="ac-card">
        <div class="ac-card__header">
            <h3 class="ac-card__title">تفاصيل القيد</h3>
        </div>
        <div class="ac-detail-list">
            <div class="ac-detail-item">
                <span class="ac-detail-label">الوصف</span>
                <span class="ac-detail-value">{{ $recurringEntry->description }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">التكرار</span>
                <span class="ac-detail-value">{{ $recurringEntry->frequencyLabel() }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">تاريخ البداية</span>
                <span class="ac-detail-value">{{ $recurringEntry->start_date->format('Y/m/d') }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">الموعد التالي</span>
                <span class="ac-detail-value">
                    {{ $recurringEntry->next_run_date->format('Y/m/d') }}
                    @if($recurringEntry->isDue())
                        <span class="ac-badge ac-badge--warning" style="margin-right:.5rem;">مستحق الآن</span>
                    @endif
                </span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">آخر تشغيل</span>
                <span class="ac-detail-value">{{ $recurringEntry->last_run_date?->format('Y/m/d') ?? 'لم يُشغَّل بعد' }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">تاريخ الانتهاء</span>
                <span class="ac-detail-value">{{ $recurringEntry->end_date?->format('Y/m/d') ?? 'بلا نهاية' }}</span>
            </div>
        </div>
    </div>

    {{-- Lines Card --}}
    <div class="ac-card">
        <div class="ac-card__header">
            <h3 class="ac-card__title">سطور القيد</h3>
        </div>
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الحساب</th>
                    <th>نوع</th>
                    <th style="text-align:left;">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recurringEntry->lines as $line)
                <tr>
                    <td>{{ $accounts[$line['account_id']] ?? '(حذف)' }}</td>
                    <td>
                        <span class="ac-badge {{ $line['type'] === 'debit' ? 'ac-badge--info' : 'ac-badge--warning' }}">
                            {{ $line['type'] === 'debit' ? 'مدين' : 'دائن' }}
                        </span>
                    </td>
                    <td style="text-align:left;font-weight:600;">{{ number_format($line['amount'],2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background:var(--ac-bg);font-weight:700;">
                    <td colspan="2">الإجمالي</td>
                    <td style="text-align:left;">{{ number_format($recurringEntry->totalDebit(),2) }}</td>
                </tr>
            </tfoot>
        </table>

        @if(!$recurringEntry->isBalanced())
        <div class="ac-alert ac-alert--danger" style="margin-top:1rem;">
            تحذير: القيد غير متوازن — مجموع المدين ≠ مجموع الدائن
        </div>
        @endif
    </div>

</div>

@endsection
