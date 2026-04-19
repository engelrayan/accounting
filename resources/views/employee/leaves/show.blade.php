@extends('employee._layout')
@section('title', 'تفاصيل طلب الإجازة #' . $leave->id)

@section('topbar-actions')
    <a href="{{ route('employee.leaves.index') }}" class="ac-btn ac-btn--ghost">← العودة للقائمة</a>


@endsection

@section('content')

@php
    $reviewMoment = $leave->reviewed_at ?? $leave->created_at;
@endphp

<div class="ac-leave-show">
    <section class="ac-leave-show__hero">
        <div class="ac-leave-show__hero-copy">
            <span class="ac-leave-show__eyebrow">طلب إجازة</span>
            <h2 class="ac-leave-show__hero-title">{{ $leave->leaveType->name }}</h2>
            <p class="ac-leave-show__hero-text">متابعة حالة الطلب ومراجعته من مكان واحد بدل تفاصيل متناثرة وصعبة القراءة.</p>

            <div class="ac-leave-show__hero-meta">
                <span>{{ $leave->start_date->format('Y/m/d') }} - {{ $leave->end_date->format('Y/m/d') }}</span>
                <span>{{ $leave->days }} يوم</span>
                <span>{{ $leave->statusLabel() }}</span>
            </div>
        </div>

        <div class="ac-leave-show__hero-id">#{{ $leave->id }}</div>
    </section>

    <section class="ac-leave-show__stats">
        <div class="ac-leave-show__stat-card">
            <span class="ac-leave-show__stat-label">الحالة الحالية</span>
            <strong class="ac-leave-show__stat-value">{{ $leave->statusLabel() }}</strong>
            <span class="ac-leave-show__stat-note">آخر تحديث {{ $reviewMoment->format('Y/m/d H:i') }}</span>
        </div>

        <div class="ac-leave-show__stat-card">
            <span class="ac-leave-show__stat-label">مدة الإجازة</span>
            <strong class="ac-leave-show__stat-value">{{ $leave->days }} يوم</strong>
            <span class="ac-leave-show__stat-note">من {{ $leave->start_date->format('Y/m/d') }}</span>
        </div>

        <div class="ac-leave-show__stat-card">
            <span class="ac-leave-show__stat-label">تاريخ الطلب</span>
            <strong class="ac-leave-show__stat-value">{{ $leave->created_at->format('Y/m/d') }}</strong>
            <span class="ac-leave-show__stat-note">{{ $leave->created_at->format('H:i') }}</span>
        </div>
    </section>

<div class="ac-grid-2 ac-leave-show__grid">

    <div class="ac-card ac-leave-show__card">
        <div class="ac-card__header">
            <h3 class="ac-card__title">بيانات الطلب</h3>
            <span class="ac-badge {{ $leave->statusBadgeClass() }}">{{ $leave->statusLabel() }}</span>
        </div>

        <div class="ac-detail-list">
            <div class="ac-detail-item">
                <span class="ac-detail-label">نوع الإجازة</span>
                <span class="ac-detail-value">{{ $leave->leaveType->name }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">من تاريخ</span>
                <span class="ac-detail-value">{{ $leave->start_date->format('Y/m/d') }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">إلى تاريخ</span>
                <span class="ac-detail-value">{{ $leave->end_date->format('Y/m/d') }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">عدد الأيام</span>
                <span class="ac-detail-value ac-text--primary" style="font-weight:700;font-size:1.1rem;">{{ $leave->days }} يوم</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">السبب</span>
                <span class="ac-detail-value">{{ $leave->reason ?: '—' }}</span>
            </div>
            <div class="ac-detail-item">
                <span class="ac-detail-label">تاريخ الطلب</span>
                <span class="ac-detail-value">{{ $leave->created_at->format('Y/m/d H:i') }}</span>
            </div>
        </div>
    </div>

    @if($leave->reviewed_at || $leave->review_notes)
    <div class="ac-card ac-leave-show__card">
        <div class="ac-card__header">
            <h3 class="ac-card__title">قرار الإدارة</h3>
        </div>

        <div class="ac-detail-list">
            @if($leave->reviewer)
            <div class="ac-detail-item">
                <span class="ac-detail-label">تمت المراجعة بواسطة</span>
                <span class="ac-detail-value">{{ $leave->reviewer->name }}</span>
            </div>
            @endif
            @if($leave->reviewed_at)
            <div class="ac-detail-item">
                <span class="ac-detail-label">تاريخ المراجعة</span>
                <span class="ac-detail-value">{{ $leave->reviewed_at->format('Y/m/d H:i') }}</span>
            </div>
            @endif
            @if($leave->review_notes)
            <div class="ac-detail-item">
                <span class="ac-detail-label">ملاحظات</span>
                <span class="ac-detail-value">{{ $leave->review_notes }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

</div>

@if(!$leave->reviewed_at && !$leave->review_notes)
<div class="ac-leave-show__review-note">
    <strong>لا توجد مراجعة مسجلة حتى الآن.</strong>
    <span>بمجرد اعتماد الطلب أو رفضه ستظهر تفاصيل القرار هنا بشكل أوضح.</span>
</div>
@endif

@if($leave->isCancellable())
<div class="ac-leave-show__footer">
    <form method="POST" action="{{ route('employee.leaves.cancel', $leave) }}"
          onsubmit="return confirm('هل تريد إلغاء هذا الطلب؟')">
        @csrf
        <button type="submit" class="ac-btn ac-btn--danger">إلغاء الطلب</button>
    </form>
</div>
@endif

</div>

@endsection
