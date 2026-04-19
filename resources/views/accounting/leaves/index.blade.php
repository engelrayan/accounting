@extends('accounting._layout')
@section('title', 'طلبات الإجازة')

@section('topbar-actions')
    <a href="{{ route('accounting.leaves.types') }}" class="ac-btn ac-btn--ghost">
        إدارة أنواع الإجازة
    </a>
@endsection

@section('content')

{{-- ══ Stats ══════════════════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">

    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">قيد المراجعة</span>
        </div>
        <div style="font-size:1.6rem;font-weight:800;color:#b45309;">{{ $stats['pending'] }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">طلب معلّق</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#dcfce7;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22,4 12,14.01 9,11.01"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">موافق عليها</span>
        </div>
        <div style="font-size:1.6rem;font-weight:800;color:#16a34a;">{{ $stats['approved'] }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">هذا العام</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">مرفوضة</span>
        </div>
        <div style="font-size:1.6rem;font-weight:800;color:#dc2626;">{{ $stats['rejected'] }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">هذا العام</div>
    </div>

    <div style="background:#fff;border-radius:14px;padding:1.25rem 1.5rem;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:.75rem;">
            <div style="width:36px;height:36px;border-radius:10px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <span style="font-size:.8rem;color:#64748b;font-weight:600;">إجمالي الطلبات</span>
        </div>
        <div style="font-size:1.6rem;font-weight:800;color:#4f46e5;">{{ array_sum($stats) }}</div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:.15rem;">جميع الحالات</div>
    </div>

</div>

{{-- ══ Filters ══════════════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.1rem 1.5rem;margin-bottom:1.25rem;">
    <form method="GET" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;">

        <div style="display:flex;flex-direction:column;gap:.35rem;min-width:160px;">
            <label style="font-size:.78rem;color:#64748b;font-weight:600;">الحالة</label>
            <select name="status"
                    style="padding:.55rem .85rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.85rem;color:#1e293b;background:#f8fafc;outline:none;cursor:pointer;">
                <option value="">كل الحالات</option>
                <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>قيد المراجعة</option>
                <option value="approved"  {{ request('status') === 'approved'  ? 'selected' : '' }}>موافق عليها</option>
                <option value="rejected"  {{ request('status') === 'rejected'  ? 'selected' : '' }}>مرفوضة</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>ملغاة</option>
            </select>
        </div>

        <div style="display:flex;flex-direction:column;gap:.35rem;min-width:160px;">
            <label style="font-size:.78rem;color:#64748b;font-weight:600;">الموظف</label>
            <select name="employee_id"
                    style="padding:.55rem .85rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.85rem;color:#1e293b;background:#f8fafc;outline:none;cursor:pointer;">
                <option value="">كل الموظفين</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                        {{ $emp->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;flex-direction:column;gap:.35rem;min-width:150px;">
            <label style="font-size:.78rem;color:#64748b;font-weight:600;">نوع الإجازة</label>
            <select name="leave_type_id"
                    style="padding:.55rem .85rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.85rem;color:#1e293b;background:#f8fafc;outline:none;cursor:pointer;">
                <option value="">كل الأنواع</option>
                @foreach($leaveTypes as $lt)
                    <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>
                        {{ $lt->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:.5rem;align-items:flex-end;">
            <button type="submit"
                    style="padding:.55rem 1.25rem;background:#4f46e5;color:#fff;border:none;border-radius:9px;font-family:inherit;font-size:.85rem;font-weight:600;cursor:pointer;">
                بحث
            </button>
            <a href="{{ route('accounting.leaves.index') }}"
               style="padding:.55rem 1rem;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:9px;font-size:.85rem;font-weight:600;text-decoration:none;display:inline-block;">
                إعادة تعيين
            </a>
        </div>

    </form>
</div>

{{-- ══ Table ════════════════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">

    <div style="padding:1rem 1.5rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
        <div style="width:32px;height:32px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
        </div>
        <span style="font-weight:700;color:#0f172a;">قائمة طلبات الإجازة</span>
        @if(request()->hasAny(['status','employee_id','leave_type_id']))
            <span style="margin-right:auto;font-size:.78rem;background:#ede9fe;color:#6d28d9;padding:.2rem .65rem;border-radius:20px;font-weight:600;">
                نتائج مصفّاة
            </span>
        @endif
    </div>

    @if($leaves->isEmpty())
        <div style="padding:4rem;text-align:center;">
            <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;">
                <rect x="3" y="4" width="18" height="18" rx="2"/>
                <line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/>
                <line x1="3" y1="10" x2="21" y2="10"/>
                <path d="M9 16l2 2 4-4"/>
            </svg>
            <p style="color:#94a3b8;font-size:.9rem;">لا توجد طلبات إجازة</p>
        </div>
    @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.75rem 1.5rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الموظف</th>
                        <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">نوع الإجازة</th>
                        <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الفترة</th>
                        <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الأيام</th>
                        <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الحالة</th>
                        <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">تاريخ الطلب</th>
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
                    <tr style="border-bottom:1px solid #f8fafc;"
                        onmouseover="this.style.background='#fafbff'"
                        onmouseout="this.style.background=''">

                        <td style="padding:.85rem 1.5rem;">
                            <div style="font-weight:600;color:#1e293b;font-size:.875rem;">{{ $leave->employee->name }}</div>
                            <div style="font-size:.78rem;color:#94a3b8;">{{ $leave->employee->employee_number }}</div>
                        </td>

                        <td style="padding:.85rem 1rem;">
                            <span style="font-size:.85rem;color:#374151;font-weight:500;">{{ $leave->leaveType->name }}</span>
                        </td>

                        <td style="padding:.85rem 1rem;">
                            <div style="font-size:.82rem;color:#475569;white-space:nowrap;">
                                {{ $leave->start_date->format('Y/m/d') }}
                            </div>
                            <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">
                                — {{ $leave->end_date->format('Y/m/d') }}
                            </div>
                        </td>

                        <td style="padding:.85rem 1rem;text-align:center;">
                            <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .65rem;border-radius:20px;font-size:.8rem;font-weight:700;">
                                {{ $leave->days }}
                            </span>
                        </td>

                        <td style="padding:.85rem 1rem;text-align:center;">
                            <span style="display:inline-flex;align-items:center;gap:.35rem;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;white-space:nowrap;">
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['dot'] }};flex-shrink:0;"></span>
                                {{ $sc['label'] }}
                            </span>
                        </td>

                        <td style="padding:.85rem 1rem;font-size:.82rem;color:#64748b;white-space:nowrap;">
                            {{ $leave->created_at->format('Y/m/d') }}
                        </td>

                        <td style="padding:.85rem 1.5rem;text-align:left;">
                            <div style="display:flex;gap:.4rem;justify-content:flex-end;align-items:center;flex-wrap:wrap;">
                                <a href="{{ route('accounting.leaves.show', $leave) }}"
                                   style="padding:.3rem .85rem;background:#ede9fe;color:#5b21b6;border:1px solid #ddd6fe;border-radius:7px;font-size:.78rem;font-weight:700;text-decoration:none;white-space:nowrap;">
                                    عرض
                                </a>
                                @if($leave->isPending())
                                    <form method="POST" action="{{ route('accounting.leaves.approve', $leave) }}"
                                          onsubmit="return confirm('الموافقة على الطلب؟')">
                                        @csrf
                                        <button type="submit"
                                                style="padding:.3rem .85rem;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                            ✓ موافقة
                                        </button>
                                    </form>
                                    <button type="button"
                                            onclick="showRejectModal({{ $leave->id }})"
                                            style="padding:.3rem .85rem;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                        ✕ رفض
                                    </button>
                                @endif
                            </div>
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

{{-- ══ Reject Modal ════════════════════════════════════════════════════════ --}}
<div id="rejectModal"
     style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.5);align-items:center;justify-content:center;backdrop-filter:blur(2px);">
    <div style="background:#fff;border-radius:16px;padding:2rem;width:100%;max-width:460px;margin:1rem;box-shadow:0 20px 60px rgba(0,0,0,.2);">

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:.75rem;">
                <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;display:flex;align-items:center;justify-content:center;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <div>
                    <div style="font-weight:800;color:#0f172a;font-size:1rem;">رفض الطلب</div>
                    <div style="font-size:.8rem;color:#94a3b8;">يرجى ذكر سبب الرفض</div>
                </div>
            </div>
            <button onclick="closeRejectModal()"
                    style="width:32px;height:32px;border:none;background:#f1f5f9;border-radius:8px;cursor:pointer;font-size:1.1rem;color:#64748b;display:flex;align-items:center;justify-content:center;">
                ×
            </button>
        </div>

        <form method="POST" id="rejectForm">
            @csrf
            <div style="margin-bottom:1.25rem;">
                <label style="display:block;font-size:.85rem;color:#374151;font-weight:600;margin-bottom:.5rem;">
                    سبب الرفض <span style="color:#ef4444;">*</span>
                </label>
                <textarea name="review_notes" rows="4" required
                          placeholder="اكتب سبب الرفض بشكل واضح..."
                          style="width:100%;padding:.75rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;resize:vertical;outline:none;"></textarea>
            </div>
            <div style="display:flex;gap:.75rem;">
                <button type="submit"
                        style="flex:1;padding:.75rem;background:linear-gradient(135deg,#dc2626,#ef4444);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;">
                    تأكيد الرفض
                </button>
                <button type="button" onclick="closeRejectModal()"
                        style="padding:.75rem 1.25rem;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:600;cursor:pointer;">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
const rejectRoutes = {
    @foreach($leaves as $leave)
    {{ $leave->id }}: "{{ route('accounting.leaves.reject', $leave) }}",
    @endforeach
};

function showRejectModal(id) {
    document.getElementById('rejectForm').action = rejectRoutes[id];
    document.getElementById('rejectModal').style.display = 'flex';
}

function closeRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
}

document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
@endpush

@endsection
