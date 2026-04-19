@extends('employee._layout')
@section('title', 'طلبات إجازة الفريق')

@section('content')

<div style="max-width:950px;margin:0 auto;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
        <div style="position:absolute;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.06);top:-70px;left:-50px;"></div>
        <div style="position:absolute;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.06);bottom:-40px;right:6%;"></div>
        <div style="position:relative;display:flex;align-items:center;gap:1rem;">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div style="color:#fff;">
                <div style="font-size:1.15rem;font-weight:800;">طلبات إجازة الفريق</div>
                <div style="opacity:.75;font-size:.85rem;">مراجعة واعتماد طلبات موظفيك المباشرين</div>
            </div>
            @if($pendingCount > 0)
            <div style="margin-right:auto;">
                <span style="background:rgba(255,255,255,.2);color:#fff;border:1px solid rgba(255,255,255,.35);padding:.4rem 1rem;border-radius:20px;font-size:.85rem;font-weight:700;">
                    {{ $pendingCount }} طلب بانتظار مراجعتك
                </span>
            </div>
            @endif
        </div>
    </div>

    {{-- Filter tabs --}}
    <div style="display:flex;gap:.5rem;margin-bottom:1.25rem;flex-wrap:wrap;">
        @php
            $filters = [
                ''          => ['label' => 'الكل', 'bg' => '#f1f5f9', 'active_bg' => '#1e293b', 'color' => '#64748b', 'active_color' => '#fff'],
                'pending'   => ['label' => 'قيد المراجعة', 'bg' => '#fef9c3', 'active_bg' => '#b45309', 'color' => '#854d0e', 'active_color' => '#fff'],
                'approved'  => ['label' => 'موافق عليها', 'bg' => '#dcfce7', 'active_bg' => '#16a34a', 'color' => '#15803d', 'active_color' => '#fff'],
                'rejected'  => ['label' => 'مرفوضة', 'bg' => '#fee2e2', 'active_bg' => '#dc2626', 'color' => '#991b1b', 'active_color' => '#fff'],
            ];
            $currentStatus = request('status', '');
        @endphp
        @foreach($filters as $val => $f)
        <a href="{{ route('employee.leaves.team', $val ? ['status' => $val] : []) }}"
           style="padding:.4rem 1rem;border-radius:20px;font-size:.82rem;font-weight:700;text-decoration:none;
               background:{{ $currentStatus === $val ? $f['active_bg'] : $f['bg'] }};
               color:{{ $currentStatus === $val ? $f['active_color'] : $f['color'] }};">
            {{ $f['label'] }}
        </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">

        @if($leaves->isEmpty())
            <div style="padding:4rem;text-align:center;">
                <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
                <p style="color:#94a3b8;font-size:.9rem;">
                    {{ $currentStatus ? 'لا توجد طلبات بهذه الحالة' : 'لا توجد طلبات إجازة من فريقك بعد' }}
                </p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f8fafc;">
                            <th style="padding:.75rem 1.5rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الموظف</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">نوع الإجازة</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;white-space:nowrap;">الفترة</th>
                            <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">الأيام</th>
                            <th style="padding:.75rem 1rem;text-align:right;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">السبب</th>
                            <th style="padding:.75rem 1rem;text-align:center;font-size:.78rem;color:#64748b;font-weight:600;border-bottom:1px solid #f1f5f9;">الحالة</th>
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
                            onmouseover="this.style.background='#f0fdf4'"
                            onmouseout="this.style.background=''">

                            <td style="padding:.85rem 1.5rem;">
                                <div style="display:flex;align-items:center;gap:.65rem;">
                                    <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,#059669,#34d399);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.85rem;flex-shrink:0;">
                                        {{ mb_substr($leave->employee->name, 0, 1) }}
                                    </div>
                                    <div>
                                        <div style="font-weight:600;color:#1e293b;font-size:.875rem;">{{ $leave->employee->name }}</div>
                                        <div style="font-size:.75rem;color:#94a3b8;">{{ $leave->employee->employee_number }}</div>
                                    </div>
                                </div>
                            </td>

                            <td style="padding:.85rem 1rem;font-size:.85rem;color:#374151;white-space:nowrap;">
                                {{ $leave->leaveType->name }}
                            </td>

                            <td style="padding:.85rem 1rem;">
                                <div style="font-size:.82rem;color:#475569;white-space:nowrap;">{{ $leave->start_date->format('Y/m/d') }}</div>
                                <div style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">— {{ $leave->end_date->format('Y/m/d') }}</div>
                            </td>

                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="background:#ede9fe;color:#5b21b6;padding:.2rem .65rem;border-radius:20px;font-size:.8rem;font-weight:700;">
                                    {{ $leave->days }}
                                </span>
                            </td>

                            <td style="padding:.85rem 1rem;font-size:.82rem;color:#64748b;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                {{ $leave->reason ? \Str::limit($leave->reason, 30) : '—' }}
                            </td>

                            <td style="padding:.85rem 1rem;text-align:center;">
                                <span style="display:inline-flex;align-items:center;gap:.35rem;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;white-space:nowrap;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['dot'] }};flex-shrink:0;"></span>
                                    {{ $sc['label'] }}
                                </span>
                            </td>

                            <td style="padding:.85rem 1.5rem;">
                                @if($leave->isPending())
                                <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('employee.leaves.team.approve', $leave) }}"
                                          onsubmit="return confirm('الموافقة على إجازة {{ $leave->employee->name }}؟')">
                                        @csrf
                                        <button type="submit"
                                                style="padding:.3rem .85rem;background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                            ✓ موافقة
                                        </button>
                                    </form>
                                    {{-- Reject --}}
                                    <button type="button"
                                            onclick="showRejectModal({{ $leave->id }}, '{{ addslashes($leave->employee->name) }}')"
                                            style="padding:.3rem .85rem;background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:7px;font-family:inherit;font-size:.78rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                                        ✕ رفض
                                    </button>
                                </div>
                                @else
                                    <span style="font-size:.75rem;color:#94a3b8;white-space:nowrap;">
                                        {{ $leave->reviewed_at?->format('Y/m/d') ?? '—' }}
                                    </span>
                                @endif
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

{{-- Reject Modal --}}
<div id="rejectModal"
     style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,.5);align-items:center;justify-content:center;backdrop-filter:blur(2px);">
    <div style="background:#fff;border-radius:16px;padding:2rem;width:100%;max-width:440px;margin:1rem;box-shadow:0 20px 60px rgba(0,0,0,.2);">

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
                    <div id="rejectEmployeeName" style="font-size:.8rem;color:#94a3b8;"></div>
                </div>
            </div>
            <button onclick="closeRejectModal()"
                    style="width:32px;height:32px;border:none;background:#f1f5f9;border-radius:8px;cursor:pointer;font-size:1.2rem;color:#64748b;">
                ×
            </button>
        </div>

        <form method="POST" id="rejectForm">
            @csrf
            <div style="margin-bottom:1.25rem;">
                <label style="display:block;font-size:.85rem;color:#374151;font-weight:600;margin-bottom:.5rem;">
                    سبب الرفض <span style="color:#ef4444;">*</span>
                </label>
                <textarea name="review_notes" rows="3" required
                          placeholder="اكتب سبب الرفض بوضوح..."
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
    {{ $leave->id }}: "{{ route('employee.leaves.team.reject', $leave) }}",
    @endforeach
};

function showRejectModal(id, name) {
    document.getElementById('rejectForm').action = rejectRoutes[id];
    document.getElementById('rejectEmployeeName').textContent = 'رفض إجازة: ' + name;
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
