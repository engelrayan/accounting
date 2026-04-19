@extends('accounting._layout')
@section('title', 'الميزانية التقديرية')

@section('topbar-actions')
    <a href="{{ route('accounting.budget.create') }}" class="ac-btn ac-btn--primary">+ ميزانية جديدة</a>
@endsection

@section('content')

@if($budgets->isEmpty())
    <div class="ac-card ac-empty-state" style="text-align:center;padding:3rem;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;margin:0 auto 1rem;color:var(--ac-muted);">
            <path d="M12 20V10"/><path d="M18 20V4"/><path d="M6 20v-4"/>
        </svg>
        <p style="color:var(--ac-muted);margin-bottom:1.5rem;">لا توجد ميزانيات تقديرية بعد</p>
        <a href="{{ route('accounting.budget.create') }}" class="ac-btn ac-btn--primary">إنشاء الميزانية الأولى</a>
    </div>
@else
    <div class="ac-card">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>السنة المالية</th>
                    <th>الحالة</th>
                    <th>إجمالي الميزانية</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($budgets as $budget)
                <tr>
                    <td style="font-weight:600;">{{ $budget->name }}</td>
                    <td>{{ $budget->fiscal_year }}</td>
                    <td>
                        <span class="ac-badge {{ $budget->statusBadgeClass() }}">{{ $budget->statusLabel() }}</span>
                    </td>
                    <td>{{ number_format($budget->totalBudget(), 2) }}</td>
                    <td>
                        <a href="{{ route('accounting.budget.show', $budget) }}" class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
