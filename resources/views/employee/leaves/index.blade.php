@extends('employee._layout')
@section('title', 'إجازاتي')

@section('content')

<div style="max-width:900px;margin:0 auto;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
        <div style="position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);top:-70px;left:-50px;"></div>
        <div style="position:absolute;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.06);bottom:-40px;right:6%;"></div>
        <div style="position:relative;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div style="display:flex;align-items:center;gap:1rem;">
                <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                        <path d="M9 16l2 2 4-4"/>
                    </svg>
                </div>
                <div style="color:#fff;">
                    <div style="font-size:1.15rem;font-weight:800;">طلبات الإجازة</div>
                    <div style="opacity:.75;font-size:.85rem;">سجل جميع طلبات إجازاتك وحالتها</div>
                </div>
            </div>
            <a href="{{ route('employee.leaves.create') }}"
               style="display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);padding:.6rem 1.25rem;border-radius:10px;font-size:.875rem;font-weight:700;text-decoration:none;backdrop-filter:blur(6px);">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                طلب إجازة جديد
            </a>
        </div>
    </div>

    {{-- Stats --}}
    @php
        $allLeaves   = $leaves->getCollection();
        $totalCount  = $leaves->total();
        $pendingCnt  = $allLeaves->where('status','pending')->count();
        $approvedCnt = $allLeaves->where('status','approved')->count();
        $totalDays   = $allLeaves->where('status','approved')->sum('days');
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1rem;margin-bottom:1.5rem;">

        <div style="background:#fff;border-radius:13px;padding:1.1rem 1.25rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                <div style="width:32px;height:32px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
                <span style="font-size:.78rem;color:#64748b;font-weight:600;">إجمالي الطلبات</span>
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#4f46e5;">{{ $totalCount }}</div>
        </div>

        <div style="background:#fff;border-radius:13px;padding:1.1rem 1.25rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                <div style="width:32px;height:32px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
                <span style="font-size:.78rem;color:#64748b;font-weight:600;">قيد المراجعة</span>
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#b45309;">{{ $pendingCnt }}</div>
        </div>

        <div style="background:#fff;border-radius:13px;padding:1.1rem 1.25rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                <div style="width:32px;height:32px;border-radius:8px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <polyline points="22,4 12,14.01 9,11.01"/>
                    </svg>
                </div>
                <span style="font-size:.78rem;color:#64748b;font-weight:600;">موافق عليها</span>
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#16a34a;">{{ $approvedCnt }}</div>
        </div>

        <div style="background:#fff;border-radius:13px;padding:1.1rem 1.25rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
            <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;">
                <div style="width:32px;height:32px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12,6 12,12 16,14"/>
                    </svg>
                </div>
                <span style="font-size:.78rem;color:#64748b;font-weight:600;">أيام مستخدمة</span>
            </div>
            <div style="font-size:1.5rem;font-weight:800;color:#1d4ed8;">{{ $totalDays }}</div>
        </div>

    </div>

    {{-- Table Card --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">

        <div style="padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:32px;height:32px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;">سجل الإجازات</span>
        </div>

        @if($leaves->isEmpty())
            <div style="padding:4rem;text-align:center;">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                <p style="color:#94a3b8;font-size:.9rem;margin-bottom:1.25rem;">لا توجد طلبات إجازة بعد</p>
                <a href="{{ route('employee.leaves.create') }}"
                   style="display:inline-block;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;padding:.65rem 1.5rem;border-radius:9px;font-size:.875rem;font-weight:700;text-decoration:none;box-shadow:0 4px 12px rgba(79,70,229,.3);">
                    + قدّم طلبك الأول
                </a>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:.75rem 1.5rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">نوع الإجازة</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">من</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">إلى</th>
                            <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الأيام</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">السبب</th>
                            <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الحالة</th>
                            <th style="padding:.75rem 1.5rem;border-bottom:1px solid #f1f5f9;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaves as $leave)
                        @php
                            $statusMap = [
                                'pending'   => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'قيد المراجعة','dot'=>'#d97706'],
                                'approved'  => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'موافق عليها','dot'=>'#16a34a'],
                                'rejected'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'مرفوضة','dot'=>'#dc2626'],
                                'cancelled' => ['bg'=>'#f1f5f9','color'=>'#64748b','label'=>'ملغاة','dot'=>'#94a3b8'],
                            ];
                            $sc = $statusMap[$leave->status] ?? $statusMap['cancelled'];
                        @endphp
                        <tr style="border-bottom:1px solid #f8fafc;transition:background .15s;"
                            onmouseover="this.style.background='#fafbff'"
                            onmouseout="this.style.background=''">

                            <td style="padding:.85rem 1.5rem;">
                                <span style="font-weight:600;color:#1e293b;font-size:.875rem;">{{ $leave->leaveType->name }}</span>
                            </td>
                            <td style="padding:.85rem 1rem;font-size:.82rem;color:#475569;white-space:nowrap;">
                                {{ $leave->start_date->format('Y/m/d') }}
                            </td>
                            <td style="padding:.85rem 1rem;font-size:.82rem;color:#475569;white-space:nowrap;">
                                {{ $leave->end_date->format('Y/m/d') }}
                            </td>
                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .65rem;border-radius:20px;font-size:.8rem;font-weight:700;">
                                    {{ $leave->days }}
                                </span>
                            </td>
                            <td style="padding:.85rem 1rem;font-size:.82rem;color:#64748b;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $leave->reason ? \Str::limit($leave->reason, 35) : '—' }}
                            </td>
                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="display:inline-flex;align-items:center;gap:.35rem;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;white-space:nowrap;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['dot'] }};display:inline-block;flex-shrink:0;"></span>
                                    {{ $sc['label'] }}
                                </span>
                            </td>
                            <td style="padding:.85rem 1.5rem;text-align:left;">
                                <a href="{{ route('employee.leaves.show', $leave) }}"
                                   style="font-size:.8rem;color:#4f46e5;font-weight:600;text-decoration:none;white-space:nowrap;">
                                    عرض ←
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($leaves->hasPages())
            <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;">
                {{ $leaves->links() }}
            </div>
            @endif
        @endif

    </div>
</div>

@endsection
