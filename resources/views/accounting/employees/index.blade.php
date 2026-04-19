@extends('accounting._layout')

@section('title', 'الموظفون')

@section('topbar-actions')
<div style="display:flex;gap:.75rem;align-items:center;">
    <a href="{{ route('employee.login') }}" target="_blank"
       class="ac-btn ac-btn--ghost"
       title="فتح بوابة الموظف في تبويب جديد">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-left:.35rem;">
            <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6"/>
            <polyline points="15,3 21,3 21,9"/>
            <line x1="10" y1="14" x2="21" y2="3"/>
        </svg>
        بوابة الموظف
    </a>
    @can('can-write')
    <a href="{{ route('accounting.employees.create') }}" class="ac-btn ac-btn--primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        موظف جديد
    </a>
    @endcan
</div>
@endsection

@section('content')

@include('accounting._flash')

{{-- ── بطاقات ──────────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الموظفين</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalCount) }}</div>
        <div class="ac-dash-card__footer">{{ $activeCount }} نشط</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي الرواتب الأساسية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($totalSalary, 2) }}</div>
        <div class="ac-dash-card__footer">للموظفين النشطين شهرياً</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">غير نشط</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--red">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $totalCount - $activeCount }}</div>
        <div class="ac-dash-card__footer">موظف معطّل</div>
    </div>

</div>

{{-- ── بانر بوابة الموظف ───────────────────────────────────────────────── --}}
<div class="ac-card" style="margin-bottom:1.5rem;padding:1rem 1.25rem;background:linear-gradient(135deg,#eef2ff 0%,#f0fdf4 100%);border:1px solid #c7d2fe;border-radius:10px;">
    <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="font-weight:700;margin-bottom:.25rem;color:#3730a3;">
                🔑 كيف يسجّل الموظف دخوله؟
            </div>
            <div style="font-size:.875rem;color:#4338ca;line-height:1.7;">
                الرابط:
                <code style="background:#e0e7ff;padding:.1rem .4rem;border-radius:4px;font-family:monospace;">
                    {{ route('employee.login') }}
                </code>
                <br>
                اسم المستخدم: <strong>رقم الجوال</strong> أو <strong>رقم الموظف</strong>
                <br>
                كلمة المرور الافتراضية: <strong>رقم الجوال</strong> (أو رقم الموظف إن لم يُدخَل جوال)
            </div>
        </div>
        <a href="{{ route('employee.login') }}" target="_blank" class="ac-btn ac-btn--primary ac-btn--sm" style="white-space:nowrap;">
            فتح البوابة ↗
        </a>
    </div>
</div>

{{-- ── فلتر ─────────────────────────────────────────────────────────────── --}}
<form method="GET" action="{{ route('accounting.employees.index') }}" class="ac-filter-bar">
    <input type="text" name="q" value="{{ request('q') }}"
           class="ac-control ac-control--sm"
           placeholder="بحث بالاسم أو الرقم أو القسم...">

    <select name="status" class="ac-select ac-select--sm">
        <option value="">كل الحالات</option>
        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>نشط</option>
        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشط</option>
    </select>

    @if($departments->isNotEmpty())
    <select name="department" class="ac-select ac-select--sm">
        <option value="">كل الأقسام</option>
        @foreach($departments as $dep)
            <option value="{{ $dep }}" {{ request('department') === $dep ? 'selected' : '' }}>{{ $dep }}</option>
        @endforeach
    </select>
    @endif

    <button type="submit" class="ac-btn ac-btn--secondary ac-btn--sm">تصفية</button>

    @if(request()->hasAny(['q','status','department']))
        <a href="{{ route('accounting.employees.index') }}" class="ac-btn ac-btn--ghost ac-btn--sm">مسح</a>
    @endif
</form>

{{-- ── جدول ─────────────────────────────────────────────────────────────── --}}
@if($employees->isEmpty())
    <div class="ac-empty">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
            <circle cx="9" cy="7" r="4"/>
        </svg>
        <div>
            <p style="font-weight:700;margin:0">لا يوجد موظفون</p>
            <p style="font-size:13px;color:var(--ac-text-muted);margin:4px 0 0">أضف موظفيك لتتمكن من إنشاء مسيرات الرواتب.</p>
        </div>
        @can('can-write')
        <a href="{{ route('accounting.employees.create') }}" class="ac-btn ac-btn--primary">إضافة موظف</a>
        @endcan
    </div>
@else
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>رقم الموظف</th>
                        <th>الاسم</th>
                        <th>القسم / المنصب</th>
                        <th>رقم الجوال</th>
                        <th>تاريخ التعيين</th>
                        <th style="text-align:left">الراتب الأساسي</th>
                        <th>طريقة الدفع</th>
                        <th style="text-align:center">الحالة</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $emp)
                    <tr class="{{ !$emp->isActive() ? 'ac-table-row--muted' : '' }}">
                        <td style="font-family:monospace;font-size:.82rem;color:var(--ac-text-muted)">
                            {{ $emp->employee_number }}
                        </td>
                        <td>
                            <div style="font-weight:600">{{ $emp->name }}</div>
                            @if($emp->email)
                                <div style="font-size:.75rem;color:var(--ac-text-muted)">{{ $emp->email }}</div>
                            @endif
                        </td>
                        <td style="color:var(--ac-text-muted);font-size:.85rem">
                            @if($emp->department || $emp->position)
                                <div>{{ $emp->department }}</div>
                                @if($emp->position)
                                    <div style="font-size:.78rem">{{ $emp->position }}</div>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td style="font-size:.85rem;color:var(--ac-text-muted);" dir="ltr">
                            {{ $emp->phone ?? '—' }}
                        </td>
                        <td style="color:var(--ac-text-muted);font-size:.85rem">
                            {{ $emp->hire_date->format('Y-m-d') }}
                        </td>
                        <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600">
                            {{ number_format($emp->basic_salary, 2) }}
                        </td>
                        <td style="font-size:.82rem;color:var(--ac-text-muted)">
                            {{ $emp->iban ? 'بنكي' : ($emp->bank_account ? 'بنكي' : 'نقداً') }}
                        </td>
                        <td style="text-align:center">
                            @if($emp->isActive())
                                <span class="ac-badge ac-badge--success">نشط</span>
                            @else
                                <span class="ac-badge ac-badge--muted">غير نشط</span>
                            @endif
                        </td>
                        <td>
                            <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap;">
                                @can('can-write')
                                <a href="{{ route('accounting.employees.edit', $emp) }}"
                                   class="ac-btn ac-btn--secondary ac-btn--sm">تعديل</a>
                                <form method="POST" action="{{ route('accounting.employees.toggle', $emp) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                                        {{ $emp->isActive() ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('accounting.employees.reset-password', $emp) }}" style="display:inline"
                                      onsubmit="return confirm('إعادة تعيين كلمة المرور لـ {{ addslashes($emp->name) }}؟\nستصبح: {{ addslashes($emp->phone ?? $emp->employee_number) }}')">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm" title="إعادة تعيين كلمة مرور الموظف">
                                        🔑
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
    <div style="margin-top:16px">{{ $employees->links() }}</div>
@endif

@endsection
