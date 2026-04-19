@extends('accounting._layout')
@section('title', 'تفاصيل طلب الإجازة')

@section('topbar-actions')
    <a href="{{ route('accounting.leaves.index') }}" class="ac-btn ac-btn--ghost">← طلبات الإجازة</a>
@endsection

@section('content')

@php
    $statusMap = [
        'pending'   => ['bg'=>'#fef9c3','color'=>'#854d0e','label'=>'قيد المراجعة','dot'=>'#d97706','icon'=>'⏳'],
        'approved'  => ['bg'=>'#dcfce7','color'=>'#15803d','label'=>'موافق عليها','dot'=>'#16a34a','icon'=>'✓'],
        'rejected'  => ['bg'=>'#fee2e2','color'=>'#991b1b','label'=>'مرفوضة','dot'=>'#dc2626','icon'=>'✕'],
        'cancelled' => ['bg'=>'#f1f5f9','color'=>'#64748b','label'=>'ملغاة','dot'=>'#94a3b8','icon'=>'—'],
    ];
    $sc = $statusMap[$leave->status] ?? $statusMap['cancelled'];
@endphp

{{-- ══ Header ════════════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-40px;"></div>
    <div style="position:relative;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
            </div>
            <div style="color:#fff;">
                <div style="font-size:1.1rem;font-weight:800;">طلب إجازة #{{ $leave->id }}</div>
                <div style="opacity:.75;font-size:.83rem;">{{ $leave->leaveType->name }} — {{ $leave->days }} يوم</div>
            </div>
        </div>
        <span style="display:inline-flex;align-items:center;gap:.5rem;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.45rem 1.1rem;border-radius:20px;font-size:.85rem;font-weight:800;">
            <span style="width:8px;height:8px;border-radius:50%;background:{{ $sc['dot'] }};"></span>
            {{ $sc['label'] }}
        </span>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

    {{-- ══ Employee Info ══════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.9rem;">بيانات الموظف</span>
        </div>
        <div style="padding:.5rem 0;">
            @php
            $empRows = [
                ['الاسم',            $leave->employee->name],
                ['رقم الموظف',       $leave->employee->employee_number],
                ['القسم',            $leave->employee->department ?? '—'],
                ['المسمى الوظيفي',   $leave->employee->position ?? '—'],
                ['المدير المباشر',   $leave->employee->manager?->name ?? '—'],
            ];
            @endphp
            @foreach($empRows as [$label, $value])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
                <span style="font-size:.82rem;color:#64748b;">{{ $label }}</span>
                <span style="font-size:.875rem;font-weight:600;color:#1e293b;">{{ $value }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ══ Leave Info ═════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.9rem;">بيانات الإجازة</span>
        </div>
        <div style="padding:.5rem 0;">
            @php
            $leaveRows = [
                ['نوع الإجازة',   $leave->leaveType->name],
                ['تاريخ البداية', $leave->start_date->format('Y/m/d')],
                ['تاريخ النهاية', $leave->end_date->format('Y/m/d')],
                ['عدد الأيام',    $leave->days . ' يوم'],
                ['تاريخ الطلب',   $leave->created_at->format('Y/m/d H:i')],
            ];
            @endphp
            @foreach($leaveRows as [$label, $value])
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 1.25rem;border-bottom:1px solid #f8fafc;">
                <span style="font-size:.82rem;color:#64748b;">{{ $label }}</span>
                <span style="font-size:.875rem;font-weight:600;color:#1e293b;">{{ $value }}</span>
            </div>
            @endforeach

            {{-- Days count highlight --}}
            <div style="margin:1rem 1.25rem;background:linear-gradient(135deg,#ede9fe,#dbeafe);border-radius:12px;padding:1rem;text-align:center;">
                <div style="font-size:2.5rem;font-weight:800;color:#4f46e5;">{{ $leave->days }}</div>
                <div style="font-size:.82rem;color:#6d28d9;font-weight:600;">يوم إجازة</div>
            </div>
        </div>
    </div>

    {{-- ══ Reason ═════════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.9rem;">سبب الإجازة</span>
        </div>
        <div style="padding:1.25rem;">
            @if($leave->reason)
                <p style="font-size:.9rem;color:#374151;line-height:1.7;margin:0;">{{ $leave->reason }}</p>
            @else
                <p style="font-size:.875rem;color:#94a3b8;margin:0;font-style:italic;">لم يُذكر سبب</p>
            @endif
        </div>
    </div>

    {{-- ══ Review Info ════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:{{ $sc['bg'] }};display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="{{ $sc['dot'] }}" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.9rem;">حالة المراجعة</span>
        </div>
        <div style="padding:1.25rem;">
            @if($leave->isPending())
                <div style="text-align:center;padding:1.5rem 0;">
                    <div style="font-size:2rem;margin-bottom:.5rem;">⏳</div>
                    <div style="font-weight:700;color:#b45309;font-size:.9rem;">في انتظار المراجعة</div>
                    <div style="color:#94a3b8;font-size:.8rem;margin-top:.25rem;">لم يتم اتخاذ قرار بعد</div>
                </div>

                {{-- Action buttons --}}
                <div style="display:flex;gap:.75rem;margin-top:1rem;">
                    <form method="POST" action="{{ route('accounting.leaves.approve', $leave) }}"
                          onsubmit="return confirm('الموافقة على هذا الطلب؟')" style="flex:1;">
                        @csrf
                        <button type="submit"
                                style="width:100%;padding:.75rem;background:linear-gradient(135deg,#16a34a,#22c55e);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;">
                            ✓ الموافقة على الطلب
                        </button>
                    </form>
                    <button type="button" onclick="document.getElementById('rejectBox').style.display='block';this.parentElement.style.display='none';"
                            style="flex:1;padding:.75rem;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;">
                        ✕ رفض الطلب
                    </button>
                </div>

                {{-- Reject form (hidden initially) --}}
                <div id="rejectBox" style="display:none;margin-top:1rem;padding:1rem;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;">
                    <form method="POST" action="{{ route('accounting.leaves.reject', $leave) }}">
                        @csrf
                        <label style="display:block;font-size:.83rem;color:#991b1b;font-weight:600;margin-bottom:.5rem;">
                            سبب الرفض <span style="color:#ef4444;">*</span>
                        </label>
                        <textarea name="review_notes" rows="3" required
                                  placeholder="اكتب سبب الرفض بوضوح..."
                                  style="width:100%;padding:.65rem .9rem;border:1.5px solid #fecaca;border-radius:9px;font-family:inherit;font-size:.875rem;resize:vertical;outline:none;background:#fff;"></textarea>
                        <div style="display:flex;gap:.5rem;margin-top:.75rem;">
                            <button type="submit"
                                    style="flex:1;padding:.65rem;background:#dc2626;color:#fff;border:none;border-radius:9px;font-family:inherit;font-size:.875rem;font-weight:700;cursor:pointer;">
                                تأكيد الرفض
                            </button>
                            <button type="button"
                                    onclick="document.getElementById('rejectBox').style.display='none';this.closest('#rejectBox').previousElementSibling.style.display='flex';"
                                    style="padding:.65rem 1rem;background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;cursor:pointer;">
                                إلغاء
                            </button>
                        </div>
                    </form>
                </div>

            @else
                <div style="display:flex;flex-direction:column;gap:.65rem;">
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 0;border-bottom:1px solid #f8fafc;">
                        <span style="font-size:.82rem;color:#64748b;">القرار</span>
                        <span style="display:inline-flex;align-items:center;gap:.35rem;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.2rem .75rem;border-radius:20px;font-size:.8rem;font-weight:700;">
                            <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['dot'] }};"></span>
                            {{ $sc['label'] }}
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 0;border-bottom:1px solid #f8fafc;">
                        <span style="font-size:.82rem;color:#64748b;">تاريخ القرار</span>
                        <span style="font-size:.875rem;font-weight:600;color:#1e293b;">{{ $leave->reviewed_at?->format('Y/m/d H:i') ?? '—' }}</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;padding:.65rem 0;border-bottom:1px solid #f8fafc;">
                        <span style="font-size:.82rem;color:#64748b;">تمت المراجعة بواسطة</span>
                        <span style="font-size:.875rem;font-weight:600;color:#1e293b;">{{ $leave->reviewer?->name ?? '—' }}</span>
                    </div>
                    @if($leave->review_notes)
                    <div style="padding:.65rem 0;">
                        <div style="font-size:.82rem;color:#64748b;margin-bottom:.4rem;">ملاحظات</div>
                        <div style="font-size:.875rem;color:#374151;background:#f8fafc;padding:.75rem;border-radius:8px;border:1px solid #f1f5f9;">
                            {{ $leave->review_notes }}
                        </div>
                    </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>

@endsection
