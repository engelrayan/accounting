@extends('accounting._layout')
@section('title', 'أنواع الإجازة')

@section('topbar-actions')
    <a href="{{ route('accounting.leaves.index') }}" class="ac-btn ac-btn--ghost">← طلبات الإجازة</a>
@endsection

@section('content')

{{-- ══ Header ════════════════════════════════════════════════════════════════ --}}
<div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:1.6rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-40px;"></div>
    <div style="position:relative;display:flex;align-items:center;gap:1rem;">
        <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
            </svg>
        </div>
        <div style="color:#fff;">
            <div style="font-size:1.1rem;font-weight:800;">أنواع الإجازة</div>
            <div style="opacity:.75;font-size:.83rem;">إدارة وتصنيف أنواع الإجازات المتاحة للموظفين</div>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.25rem;align-items:start;">

    {{-- ══ Add Form ═══════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;position:sticky;top:1.5rem;">

        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#ede9fe;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#6d28d9" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">إضافة نوع جديد</span>
        </div>

        <div style="padding:1.25rem;">

            @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;color:#991b1b;border-radius:10px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.83rem;display:flex;gap:.5rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;margin-top:.1rem;">
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <div>@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            </div>
            @endif

            <form method="POST" action="{{ route('accounting.leaves.types.store') }}">
                @csrf

                {{-- Name --}}
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">
                        اسم نوع الإجازة <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" name="name" value="{{ old('name') }}"
                           placeholder="مثال: إجازة سنوية"
                           style="width:100%;padding:.65rem .9rem;border:1.5px solid {{ $errors->has('name') ? '#f87171' : '#e2e8f0' }};border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                </div>

                {{-- Days --}}
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">
                        عدد الأيام في السنة
                    </label>
                    <input type="number" name="days_per_year" value="{{ old('days_per_year') }}"
                           min="1" max="365" placeholder="اتركه فارغاً = غير محدود"
                           style="width:100%;padding:.65rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;">
                    <div style="font-size:.75rem;color:#94a3b8;margin-top:.3rem;">اتركه فارغاً للإجازات غير المحدودة</div>
                </div>

                {{-- Color --}}
                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">لون التمييز</label>
                    <div style="display:flex;align-items:center;gap:.75rem;">
                        <input type="color" name="color" value="{{ old('color', '#6366f1') }}"
                               id="colorPicker"
                               style="width:48px;height:40px;border:1.5px solid #e2e8f0;border-radius:9px;cursor:pointer;padding:3px;background:#fff;">
                        <span id="colorHex" style="font-size:.82rem;color:#64748b;font-family:monospace;">{{ old('color', '#6366f1') }}</span>
                    </div>
                </div>

                {{-- Requires Approval --}}
                <div style="margin-bottom:1.25rem;padding:.85rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;display:flex;align-items:center;gap:.75rem;cursor:pointer;"
                     onclick="document.getElementById('reqApproval').click()">
                    <input type="checkbox" name="requires_approval" id="reqApproval" value="1"
                           {{ old('requires_approval', true) ? 'checked' : '' }}
                           style="width:17px;height:17px;accent-color:#4f46e5;cursor:pointer;flex-shrink:0;">
                    <div>
                        <div style="font-size:.85rem;font-weight:600;color:#374151;">تتطلب موافقة الإدارة</div>
                        <div style="font-size:.75rem;color:#94a3b8;">يجب على المدير مراجعة الطلب قبل الاعتماد</div>
                    </div>
                </div>

                <button type="submit"
                        style="width:100%;padding:.75rem;background:linear-gradient(135deg,#4f46e5,#6366f1);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.9rem;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(79,70,229,.3);">
                    + حفظ النوع
                </button>
            </form>
        </div>
    </div>

    {{-- ══ List ════════════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">

        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
            <div style="display:flex;align-items:center;gap:.6rem;">
                <div style="width:30px;height:30px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                        <line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/>
                        <line x1="8" y1="18" x2="21" y2="18"/>
                        <line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/>
                        <line x1="3" y1="18" x2="3.01" y2="18"/>
                    </svg>
                </div>
                <span style="font-weight:700;color:#0f172a;font-size:.95rem;">أنواع الإجازة المضافة</span>
            </div>
            <span style="font-size:.78rem;background:#f1f5f9;color:#64748b;padding:.2rem .65rem;border-radius:20px;font-weight:600;">
                {{ $leaveTypes->count() }} نوع
            </span>
        </div>

        @if($leaveTypes->isEmpty())
            <div style="padding:4rem;text-align:center;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.5" style="margin:0 auto 1rem;display:block;">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z"/>
                </svg>
                <p style="color:#94a3b8;font-size:.875rem;">لا توجد أنواع إجازة بعد — أضف النوع الأول من النموذج</p>
            </div>
        @else
            <div style="padding:.5rem 0;">
                @foreach($leaveTypes as $lt)
                <div style="display:flex;align-items:center;gap:1rem;padding:.9rem 1.25rem;border-bottom:1px solid #f8fafc;"
                     onmouseover="this.style.background='#fafbff'"
                     onmouseout="this.style.background=''">

                    {{-- Color dot + name --}}
                    <div style="display:flex;align-items:center;gap:.75rem;flex:1;min-width:0;">
                        <div style="width:38px;height:38px;border-radius:10px;background:{{ $lt->color ?? '#6366f1' }}22;border:2px solid {{ $lt->color ?? '#6366f1' }}44;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <div style="width:12px;height:12px;border-radius:50%;background:{{ $lt->color ?? '#6366f1' }};"></div>
                        </div>
                        <div style="min-width:0;">
                            <div style="font-weight:700;color:#1e293b;font-size:.9rem;">{{ $lt->name }}</div>
                            <div style="font-size:.75rem;color:#94a3b8;">
                                {{ $lt->requires_approval ? '🔐 تتطلب موافقة' : '✓ بدون موافقة' }}
                            </div>
                        </div>
                    </div>

                    {{-- Days badge --}}
                    <div style="text-align:center;min-width:70px;">
                        @if($lt->isUnlimited())
                            <span style="background:#f0fdf4;color:#166534;padding:.25rem .7rem;border-radius:20px;font-size:.78rem;font-weight:700;">
                                ∞ غير محدود
                            </span>
                        @else
                            <span style="background:#ede9fe;color:#5b21b6;padding:.25rem .7rem;border-radius:20px;font-size:.78rem;font-weight:700;">
                                {{ $lt->days_per_year }} يوم
                            </span>
                        @endif
                    </div>

                    {{-- Status --}}
                    <div style="min-width:60px;text-align:center;">
                        <span style="display:inline-flex;align-items:center;gap:.3rem;padding:.25rem .7rem;border-radius:20px;font-size:.75rem;font-weight:700;
                            background:{{ $lt->is_active ? '#dcfce7' : '#f1f5f9' }};
                            color:{{ $lt->is_active ? '#15803d' : '#64748b' }};">
                            <span style="width:6px;height:6px;border-radius:50%;background:{{ $lt->is_active ? '#16a34a' : '#94a3b8' }};"></span>
                            {{ $lt->is_active ? 'نشط' : 'معطّل' }}
                        </span>
                    </div>

                    {{-- Toggle --}}
                    <form method="POST" action="{{ route('accounting.leaves.types.toggle', $lt) }}">
                        @csrf
                        <button type="submit"
                                style="padding:.35rem .85rem;border-radius:8px;font-family:inherit;font-size:.78rem;font-weight:600;cursor:pointer;white-space:nowrap;
                                    background:{{ $lt->is_active ? '#fef2f2' : '#f0fdf4' }};
                                    color:{{ $lt->is_active ? '#991b1b' : '#15803d' }};
                                    border:1px solid {{ $lt->is_active ? '#fecaca' : '#bbf7d0' }};">
                            {{ $lt->is_active ? 'تعطيل' : 'تفعيل' }}
                        </button>
                    </form>

                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
document.getElementById('colorPicker').addEventListener('input', function() {
    document.getElementById('colorHex').textContent = this.value;
});
</script>
@endpush

@endsection
