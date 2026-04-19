@extends('employee._layout')
@section('title', 'ملفي الشخصي')

@section('content')

{{-- ══ Hero Banner ══════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:2rem 2rem 3.5rem;margin-bottom:-2rem;position:relative;overflow:hidden;">

    {{-- deco circles --}}
    <div style="position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-60px;"></div>
    <div style="position:absolute;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);bottom:-40px;right:10%;"></div>

    <div style="position:relative;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">

        {{-- Avatar --}}
        <div style="width:80px;height:80px;border-radius:50%;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.4);display:flex;align-items:center;justify-content:center;font-size:2rem;font-weight:800;color:#fff;flex-shrink:0;">
            {{ mb_substr($employee->name, 0, 1) }}
        </div>

        <div style="color:#fff;">
            <div style="font-size:1.4rem;font-weight:800;margin-bottom:.25rem;">{{ $employee->name }}</div>
            <div style="opacity:.8;font-size:.9rem;display:flex;flex-wrap:wrap;gap:1rem;">
                <span>🪪 {{ $employee->employee_number }}</span>
                @if($employee->position) <span>💼 {{ $employee->position }}</span> @endif
                @if($employee->department) <span>🏢 {{ $employee->department }}</span> @endif
            </div>
        </div>

        <div style="margin-right:auto;">
            <span style="background:rgba(255,255,255,.2);color:#fff;padding:.35rem .9rem;border-radius:20px;font-size:.8rem;font-weight:700;border:1px solid rgba(255,255,255,.3);">
                {{ $employee->isActive() ? '✓ نشط' : '✗ غير نشط' }}
            </span>
        </div>
    </div>
</div>

{{-- ══ Quick Stats ══════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;padding:0 .5rem;">

    <div style="background:#fff;border-radius:12px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#1e40af;">{{ number_format($employee->basic_salary, 0) }}</div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.2rem;">الراتب الأساسي (ريال)</div>
    </div>

    <div style="background:#fff;border-radius:12px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:#0891b2;">
            {{ $employee->hire_date ? $employee->hire_date->diffInYears(now()) : '—' }}
        </div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.2rem;">سنوات الخدمة</div>
    </div>

    @php $leaveTypes = \App\Modules\Accounting\Models\LeaveType::forCompany($employee->company_id)->active()->get(); @endphp

    @foreach($leaveTypes->take(2) as $lt)
    @php
        $used      = $employee->usedLeaveDays($lt->id, now()->year);
        $remaining = $lt->days_per_year ? $lt->days_per_year - $used : null;
    @endphp
    <div style="background:#fff;border-radius:12px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);text-align:center;">
        <div style="font-size:1.5rem;font-weight:800;color:{{ $remaining !== null && $remaining <= 3 ? '#dc2626' : '#16a34a' }};">
            {{ $remaining !== null ? $remaining : '∞' }}
        </div>
        <div style="font-size:.8rem;color:#64748b;margin-top:.2rem;">{{ $lt->name }} (متبقٍ)</div>
    </div>
    @endforeach

</div>

{{-- ══ Main Grid ════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

    {{-- Personal Info --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">البيانات الشخصية</span>
        </div>
        <div style="padding:.5rem 0;">
            @php
            $personalRows = [
                ['رقم الموظف',        $employee->employee_number],
                ['رقم الهوية الوطنية', $employee->national_id ?? '—'],
                ['رقم الجوال',         $employee->phone ?? '—'],
                ['البريد الإلكتروني',  $employee->email ?? '—'],
            ];
            @endphp
            @foreach($personalRows as [$label, $value])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
                <span style="font-size:.82rem;color:#64748b;">{{ $label }}</span>
                <span style="font-size:.875rem;font-weight:600;color:#1e293b;" dir="ltr">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Work Info --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;background:#dcfce7;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">بيانات العمل</span>
        </div>
        <div style="padding:.5rem 0;">
            @php
            $workRows = [
                ['القسم',           $employee->department ?? '—'],
                ['المسمى الوظيفي',  $employee->position ?? '—'],
                ['تاريخ التعيين',   $employee->hire_date?->format('Y/m/d') ?? '—'],
                ['الراتب الأساسي',  number_format($employee->basic_salary, 2) . ' ريال'],
            ];
            @endphp
            @foreach($workRows as [$label, $value])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
                <span style="font-size:.82rem;color:#64748b;">{{ $label }}</span>
                <span style="font-size:.875rem;font-weight:600;color:#1e293b;">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Bank Info --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;background:#fef9c3;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                    <rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">بيانات البنك</span>
        </div>
        <div style="padding:.5rem 0;">
            @php
            $bankRows = [
                ['رقم الحساب البنكي', $employee->bank_account ?? '—'],
                ['رقم الآيبان',       $employee->iban ?? '—'],
            ];
            @endphp
            @foreach($bankRows as [$label, $value])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
                <span style="font-size:.82rem;color:#64748b;">{{ $label }}</span>
                <span style="font-size:.875rem;font-weight:600;color:#1e293b;" dir="ltr">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Leave Balances --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/><path d="M9 16l2 2 4-4"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">أرصدة الإجازات {{ now()->year }}</span>
        </div>

        @if($leaveTypes->isEmpty())
            <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:.875rem;">لا توجد أنواع إجازة محددة</div>
        @else
            <div style="padding:.75rem 1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                @foreach($leaveTypes as $lt)
                @php
                    $used      = $employee->usedLeaveDays($lt->id, now()->year);
                    $total     = $lt->days_per_year;
                    $remaining = $total ? $total - $used : null;
                    $pct       = ($total && $total > 0) ? min(100, round(($used / $total) * 100)) : 0;
                    $barColor  = $pct >= 80 ? '#ef4444' : ($pct >= 50 ? '#f59e0b' : '#22c55e');
                @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.35rem;">
                        <span style="font-size:.85rem;font-weight:600;color:#374151;">{{ $lt->name }}</span>
                        <span style="font-size:.8rem;color:#64748b;">
                            @if($lt->isUnlimited())
                                مستخدم: <strong>{{ $used }}</strong> يوم — غير محدود
                            @else
                                <strong style="color:{{ $remaining <= 3 ? '#dc2626' : '#16a34a' }};">{{ $remaining }} متبقٍ</strong>
                                من {{ $total }} يوم
                            @endif
                        </span>
                    </div>
                    @if(!$lt->isUnlimited())
                    <div style="background:#f1f5f9;border-radius:99px;height:7px;overflow:hidden;">
                        <div style="background:{{ $barColor }};width:{{ $pct }}%;height:100%;border-radius:99px;transition:width .4s;"></div>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
