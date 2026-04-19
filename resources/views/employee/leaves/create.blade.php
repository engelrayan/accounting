@extends('employee._layout')
@section('title', 'طلب إجازة جديد')

@section('content')

<div style="max-width:780px;margin:0 auto;">

    {{-- Header --}}
    <div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
        <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-40px;"></div>
        <div style="position:relative;display:flex;align-items:center;gap:1rem;">
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
                <div style="font-size:1.15rem;font-weight:800;">طلب إجازة جديد</div>
                <div style="opacity:.75;font-size:.85rem;">أدخل بيانات الإجازة وسيتم مراجعتها من الإدارة</div>
            </div>
        </div>
    </div>

    {{-- Errors --}}
    @if($errors->any())
    <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:12px;padding:.9rem 1.25rem;margin-bottom:1.25rem;display:flex;gap:.75rem;align-items:flex-start;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:.1rem;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        <ul style="margin:0;padding:0;list-style:none;font-size:.875rem;">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
    @endif

    <form method="POST" action="{{ route('employee.leaves.store') }}">
        @csrf

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">

            {{-- Left column --}}
            <div style="display:flex;flex-direction:column;gap:1.25rem;">

                {{-- Leave Type --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
                    <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;">
                        <div style="width:28px;height:28px;border-radius:7px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                            </svg>
                        </div>
                        <span style="font-weight:700;font-size:.875rem;color:#1e293b;">نوع الإجازة</span>
                    </div>
                    <div style="padding:1.25rem;">
                        <label style="display:block;font-size:.82rem;color:#64748b;margin-bottom:.5rem;font-weight:600;">اختر نوع الإجازة <span style="color:#ef4444;">*</span></label>
                        <select name="leave_type_id" id="leaveTypeSelect"
                                style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;cursor:pointer;">
                            <option value="">— اختر نوع الإجازة —</option>
                            @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}"
                                    data-days="{{ $lt->days_per_year }}"
                                    data-used="{{ $employee->usedLeaveDays($lt->id, now()->year) }}"
                                    {{ old('leave_type_id') == $lt->id ? 'selected' : '' }}>
                                {{ $lt->name }}
                            </option>
                            @endforeach
                        </select>

                        {{-- Balance indicator --}}
                        <div id="balanceBox" style="display:none;margin-top:1rem;padding:.85rem 1rem;border-radius:10px;background:#f0fdf4;border:1px solid #bbf7d0;">
                            <div style="font-size:.8rem;color:#166534;font-weight:600;" id="balanceText"></div>
                            <div style="margin-top:.5rem;background:#dcfce7;border-radius:99px;height:6px;overflow:hidden;">
                                <div id="balanceBar" style="background:#22c55e;height:100%;border-radius:99px;transition:width .4s;width:0%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Reason --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
                    <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;">
                        <div style="width:28px;height:28px;border-radius:7px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                <polyline points="14,2 14,8 20,8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                            </svg>
                        </div>
                        <span style="font-weight:700;font-size:.875rem;color:#1e293b;">السبب</span>
                        <span style="font-size:.75rem;color:#94a3b8;margin-right:auto;">(اختياري)</span>
                    </div>
                    <div style="padding:1.25rem;">
                        <textarea name="reason" rows="4"
                                  style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;resize:vertical;outline:none;"
                                  placeholder="اكتب سبب طلب الإجازة هنا...">{{ old('reason') }}</textarea>
                    </div>
                </div>

            </div>

            {{-- Right column --}}
            <div style="display:flex;flex-direction:column;gap:1.25rem;">

                {{-- Dates --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
                    <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;">
                        <div style="width:28px;height:28px;border-radius:7px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <span style="font-weight:700;font-size:.875rem;color:#1e293b;">فترة الإجازة</span>
                    </div>
                    <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">

                        <div>
                            <label style="display:block;font-size:.82rem;color:#64748b;margin-bottom:.5rem;font-weight:600;">من تاريخ <span style="color:#ef4444;">*</span></label>
                            <input type="date" name="start_date" id="startDate"
                                   value="{{ old('start_date') }}"
                                   min="{{ now()->toDateString() }}"
                                   style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                        </div>

                        <div>
                            <label style="display:block;font-size:.82rem;color:#64748b;margin-bottom:.5rem;font-weight:600;">إلى تاريخ <span style="color:#ef4444;">*</span></label>
                            <input type="date" name="end_date" id="endDate"
                                   value="{{ old('end_date') }}"
                                   min="{{ now()->toDateString() }}"
                                   style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                        </div>

                        {{-- Days counter --}}
                        <div id="daysBox" style="display:none;background:linear-gradient(135deg,#ede9fe,#dbeafe);border-radius:12px;padding:1rem;text-align:center;">
                            <div style="font-size:2rem;font-weight:800;color:#4f46e5;" id="daysCount">0</div>
                            <div style="font-size:.8rem;color:#6d28d9;font-weight:600;">يوم إجازة</div>
                        </div>

                    </div>
                </div>

                {{-- Submit --}}
                <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                    <button type="submit"
                            style="width:100%;padding:.9rem;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(79,70,229,.3);">
                        إرسال الطلب
                    </button>
                    <a href="{{ route('employee.leaves.index') }}"
                       style="display:block;width:100%;padding:.75rem;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.875rem;font-weight:600;cursor:pointer;text-align:center;text-decoration:none;">
                        إلغاء
                    </a>
                </div>

            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
const leaveTypeSelect = document.getElementById('leaveTypeSelect');
const startDateInput  = document.getElementById('startDate');
const endDateInput    = document.getElementById('endDate');
const balanceBox      = document.getElementById('balanceBox');
const balanceText     = document.getElementById('balanceText');
const balanceBar      = document.getElementById('balanceBar');
const daysBox         = document.getElementById('daysBox');
const daysCount       = document.getElementById('daysCount');

function updateBalance() {
    const opt = leaveTypeSelect.selectedOptions[0];
    if (!opt || !opt.value) { balanceBox.style.display = 'none'; return; }

    const total = parseInt(opt.dataset.days) || 0;
    const used  = parseInt(opt.dataset.used)  || 0;

    if (!opt.dataset.days) {
        balanceBox.style.background = '#f0fdf4';
        balanceBox.style.borderColor = '#bbf7d0';
        balanceText.style.color = '#166534';
        balanceText.textContent = '✓ هذا النوع غير محدود الأيام';
        balanceBar.style.width = '0%';
    } else {
        const remaining = total - used;
        const pct = Math.min(100, Math.round((used / total) * 100));
        const isLow = remaining <= 3;

        balanceBox.style.background = isLow ? '#fef2f2' : '#f0fdf4';
        balanceBox.style.borderColor = isLow ? '#fecaca' : '#bbf7d0';
        balanceText.style.color = isLow ? '#991b1b' : '#166534';
        balanceText.textContent = `رصيدك: ${remaining} يوم متبقٍ من ${total} يوم (مستخدم: ${used})`;
        balanceBar.style.background = isLow ? '#ef4444' : '#22c55e';
        balanceBar.parentElement.style.background = isLow ? '#fee2e2' : '#dcfce7';
        balanceBar.style.width = pct + '%';
    }
    balanceBox.style.display = 'block';
}

function updateDays() {
    const s = startDateInput.value;
    const e = endDateInput.value;
    if (!s || !e || e < s) { daysBox.style.display = 'none'; return; }
    const days = Math.floor((new Date(e) - new Date(s)) / 86400000) + 1;
    daysCount.textContent = days;
    daysBox.style.display = 'block';
    endDateInput.min = startDateInput.value;
}

leaveTypeSelect.addEventListener('change', updateBalance);
startDateInput.addEventListener('change', () => { endDateInput.min = startDateInput.value; updateDays(); });
endDateInput.addEventListener('change', updateDays);

updateBalance(); updateDays();
</script>
@endpush

@endsection
