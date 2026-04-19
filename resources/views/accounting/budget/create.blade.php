@extends('accounting._layout')
@section('title', 'ميزانية تقديرية جديدة')

@section('content')

{{-- Header --}}
<div style="background:linear-gradient(135deg,#1e3a5f 0%,#2563eb 100%);border-radius:16px;padding:1.6rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-40px;"></div>
    <div style="position:relative;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                    <line x1="2"  y1="20" x2="22" y2="20"/>
                </svg>
            </div>
            <div style="color:#fff;">
                <div style="font-size:1.1rem;font-weight:800;">ميزانية تقديرية جديدة</div>
                <div style="opacity:.75;font-size:.83rem;">تحديد الأهداف المالية وبنود الإيرادات والمصروفات</div>
            </div>
        </div>
        <a href="{{ route('accounting.budget.index') }}"
           style="display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.55rem 1.1rem;border-radius:9px;font-size:.85rem;font-weight:600;text-decoration:none;">
            ← رجوع للميزانيات
        </a>
    </div>
</div>

<form method="POST" action="{{ route('accounting.budget.store') }}">
@csrf

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:1.25rem;align-items:start;">

    {{-- ══ Main Info ══════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#dbeafe;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#1d4ed8" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/>
                    <line x1="12" y1="20" x2="12" y2="4"/>
                    <line x1="6"  y1="20" x2="6"  y2="14"/>
                    <line x1="2"  y1="20" x2="22" y2="20"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">بيانات الميزانية</span>
        </div>
        <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">

            {{-- Name --}}
            <div>
                <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">
                    اسم الميزانية <span style="color:#ef4444;">*</span>
                </label>
                <input type="text" name="name"
                       value="{{ old('name') }}"
                       placeholder="مثال: ميزانية 2026"
                       required
                       style="width:100%;padding:.7rem .9rem;border:1.5px solid {{ $errors->has('name') ? '#f87171' : '#e2e8f0' }};border-radius:9px;font-family:inherit;font-size:.9rem;color:#1e293b;background:#f8fafc;outline:none;transition:border .2s;"
                       onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='{{ $errors->has('name') ? '#f87171' : '#e2e8f0' }}'">
                @error('name')
                    <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fiscal Year --}}
            <div>
                <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">
                    السنة المالية <span style="color:#ef4444;">*</span>
                </label>
                <input type="number" name="fiscal_year"
                       value="{{ old('fiscal_year', $currentYear) }}"
                       min="2000" max="2100"
                       required
                       style="width:100%;padding:.7rem .9rem;border:1.5px solid {{ $errors->has('fiscal_year') ? '#f87171' : '#e2e8f0' }};border-radius:9px;font-family:inherit;font-size:.9rem;color:#1e293b;background:#f8fafc;outline:none;transition:border .2s;"
                       onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='{{ $errors->has('fiscal_year') ? '#f87171' : '#e2e8f0' }}'">
                @error('fiscal_year')
                    <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Notes --}}
            <div>
                <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">ملاحظات (اختياري)</label>
                <textarea name="notes" rows="4"
                          placeholder="أي ملاحظات أو تفاصيل إضافية..."
                          style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;resize:vertical;"
                          onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'">{{ old('notes') }}</textarea>
            </div>

        </div>
    </div>

    {{-- ══ Sidebar ═════════════════════════════════════════════════════════════ --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Steps Info --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
                <div style="width:30px;height:30px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                </div>
                <span style="font-weight:700;color:#0f172a;font-size:.95rem;">خطوات الإعداد</span>
            </div>
            <div style="padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;">
                @foreach([
                    ['1', 'إنشاء الميزانية', 'تحديد الاسم والسنة المالية', '#2563eb', '#dbeafe'],
                    ['2', 'إضافة البنود', 'تحديد الإيرادات والمصروفات المتوقعة', '#94a3b8', '#f1f5f9'],
                    ['3', 'متابعة الأداء', 'مقارنة الفعلي بالمخطط شهريًا', '#94a3b8', '#f1f5f9'],
                ] as [$num, $title, $desc, $color, $bg])
                <div style="display:flex;align-items:flex-start;gap:.75rem;">
                    <div style="width:26px;height:26px;border-radius:50%;background:{{ $bg }};color:{{ $color }};font-size:.75rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:.1rem;">{{ $num }}</div>
                    <div>
                        <div style="font-size:.82rem;font-weight:700;color:#1e293b;">{{ $title }}</div>
                        <div style="font-size:.76rem;color:#94a3b8;margin-top:.1rem;">{{ $desc }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Info Box --}}
        <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:1rem 1.1rem;display:flex;gap:.75rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#0369a1" stroke-width="2" style="flex-shrink:0;margin-top:.1rem;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div style="font-size:.8rem;color:#0c4a6e;line-height:1.6;">
                بعد إنشاء الميزانية ستنتقل مباشرةً لإضافة بنود الإيرادات والمصروفات التفصيلية.
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;flex-direction:column;gap:.6rem;">
            <button type="submit"
                    style="width:100%;padding:.85rem;background:linear-gradient(135deg,#1e3a5f,#2563eb);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(37,99,235,.3);">
                إنشاء الميزانية
            </button>
            <a href="{{ route('accounting.budget.index') }}"
               style="display:block;text-align:center;padding:.75rem;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;font-weight:600;text-decoration:none;">
                إلغاء
            </a>
        </div>

    </div>

</div>

</form>

@endsection
