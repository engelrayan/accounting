@extends('employee._layout')
@section('title', 'الرئيسية')

@section('topbar-actions')
    <a href="{{ route('employee.leaves.create') }}" class="ac-btn ac-btn--primary">
        + طلب إجازة جديد
    </a>
@endsection

@section('content')

{{-- ══ Hero Banner ══════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:2rem 2.5rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,.06);top:-80px;left:-60px;"></div>
    <div style="position:absolute;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);bottom:-50px;right:8%;"></div>

    <div style="position:relative;display:flex;align-items:center;gap:1.5rem;flex-wrap:wrap;">
        <div style="width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.35);display:flex;align-items:center;justify-content:center;font-size:1.7rem;font-weight:800;color:#fff;flex-shrink:0;">
            {{ mb_substr($employee->name, 0, 1) }}
        </div>
        <div style="color:#fff;">
            <div style="font-size:1.3rem;font-weight:800;margin-bottom:.3rem;">مرحباً، {{ $employee->name }}</div>
            <div style="opacity:.75;font-size:.875rem;display:flex;flex-wrap:wrap;gap:1.25rem;">
                <span>🪪 {{ $employee->employee_number }}</span>
                <span>📅 تاريخ التعيين: {{ $employee->hire_date?->format('Y/m/d') }}</span>
                @if($employee->department) <span>🏢 {{ $employee->department }}</span> @endif
            </div>
        </div>
    </div>
</div>

{{-- ══ Quick Stats ══════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:1rem;margin-bottom:1.5rem;">

    {{-- Salary --}}
    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">الراتب الأساسي</span>
        </div>
        <div style="font-size:1.4rem;font-weight:800;color:#1e40af;">{{ number_format($employee->basic_salary, 0) }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">ريال / شهر</div>
    </div>

    {{-- Department --}}
    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">القسم</span>
        </div>
        <div style="font-size:1rem;font-weight:800;color:#15803d;">{{ $employee->department ?? '—' }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">{{ $employee->position ?? 'غير محدد' }}</div>
    </div>

    {{-- Pending --}}
    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">قيد المراجعة</span>
        </div>
        <div style="font-size:1.4rem;font-weight:800;color:#b45309;">{{ $pendingLeaves }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">طلب إجازة</div>
    </div>

    {{-- Approved --}}
    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                    <polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">موافق عليها</span>
        </div>
        <div style="font-size:1.4rem;font-weight:800;color:#16a34a;">{{ $approvedLeaves }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">هذا العام</div>
    </div>

</div>

{{-- ══ Recent Leaves ════════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">

    <div style="padding:1.1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;">آخر طلبات الإجازة</span>
        </div>
        <a href="{{ route('employee.leaves.index') }}"
           style="font-size:.8rem;color:#4f46e5;font-weight:600;text-decoration:none;">
            عرض الكل ←
        </a>
    </div>

    @if($recentLeaves->isEmpty())
        <div style="padding:3rem;text-align:center;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto .75rem;display:block;">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/>
                <line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <p style="color:#94a3b8;font-size:.875rem;margin-bottom:1rem;">لا توجد طلبات إجازة بعد</p>
            <a href="{{ route('employee.leaves.create') }}"
               style="display:inline-block;background:#4f46e5;color:#fff;padding:.6rem 1.25rem;border-radius:8px;font-size:.875rem;font-weight:600;text-decoration:none;">
                + قدّم طلبك الأول
            </a>
        </div>
    @else
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:.75rem 1.5rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">نوع الإجازة</th>
                    <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">الفترة</th>
                    <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">الأيام</th>
                    <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">الحالة</th>
                    <th style="padding:.75rem 1.5rem;border-bottom:1px solid #f1f5f9;"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentLeaves as $leave)
                @php
                    $statusColors = [
                        'pending'   => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'قيد المراجعة'],
                        'approved'  => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'موافق عليها'],
                        'rejected'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'مرفوضة'],
                        'cancelled' => ['bg'=>'#f1f5f9','color'=>'#64748b','label'=>'ملغاة'],
                    ];
                    $sc = $statusColors[$leave->status] ?? $statusColors['cancelled'];
                @endphp
                <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafbff'" onmouseout="this.style.background=''">
                    <td style="padding:.85rem 1.5rem;">
                        <span style="font-weight:600;color:#1e293b;font-size:.875rem;">{{ $leave->leaveType->name }}</span>
                    </td>
                    <td style="padding:.85rem 1rem;font-size:.82rem;color:#64748b;">
                        {{ $leave->start_date->format('Y/m/d') }} — {{ $leave->end_date->format('Y/m/d') }}
                    </td>
                    <td style="padding:.85rem 1rem;text-align:center;">
                        <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .6rem;border-radius:20px;font-size:.8rem;font-weight:700;">
                            {{ $leave->days }} يوم
                        </span>
                    </td>
                    <td style="padding:.85rem 1rem;text-align:center;">
                        <span style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.2rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;">
                            {{ $sc['label'] }}
                        </span>
                    </td>
                    <td style="padding:.85rem 1.5rem;text-align:left;">
                        <a href="{{ route('employee.leaves.show', $leave) }}"
                           style="font-size:.8rem;color:#4f46e5;font-weight:600;text-decoration:none;">عرض</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

@endsection
