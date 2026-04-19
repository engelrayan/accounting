@extends('accounting._layout')
@section('title', 'مورد جديد')

@section('content')

{{-- Header --}}
<div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);border-radius:16px;padding:1.6rem 2rem;margin-bottom:1.5rem;position:relative;overflow:hidden;">
    <div style="position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);top:-60px;left:-40px;"></div>
    <div style="position:relative;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
        <div style="display:flex;align-items:center;gap:1rem;">
            <div style="width:46px;height:46px;border-radius:12px;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);display:flex;align-items:center;justify-content:center;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                    <path d="M16 3.13a4 4 0 010 7.75"/>
                </svg>
            </div>
            <div style="color:#fff;">
                <div style="font-size:1.1rem;font-weight:800;">مورد جديد</div>
                <div style="opacity:.75;font-size:.83rem;">إضافة مورد جديد لقاعدة بيانات الموردين</div>
            </div>
        </div>
        <a href="{{ route('accounting.vendors.index') }}"
           style="display:inline-flex;align-items:center;gap:.5rem;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);padding:.55rem 1.1rem;border-radius:9px;font-size:.85rem;font-weight:600;text-decoration:none;">
            ← رجوع للموردين
        </a>
    </div>
</div>

<form method="POST" action="{{ route('accounting.vendors.store') }}">
@csrf

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:1.25rem;align-items:start;">

    {{-- ══ Main Info ══════════════════════════════════════════════════════════ --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
            <div style="width:30px;height:30px;border-radius:8px;background:#d1fae5;display:flex;align-items:center;justify-content:center;">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#065f46" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <span style="font-weight:700;color:#0f172a;font-size:.95rem;">البيانات الأساسية</span>
        </div>
        <div style="padding:1.25rem;display:flex;flex-direction:column;gap:1rem;">

            {{-- Name --}}
            <div>
                <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">
                    اسم المورد <span style="color:#ef4444;">*</span>
                </label>
                <input id="name" name="name" type="text"
                       value="{{ old('name') }}"
                       placeholder="مثال: شركة النور للتوريدات"
                       required
                       style="width:100%;padding:.7rem .9rem;border:1.5px solid {{ $errors->has('name') ? '#f87171' : '#e2e8f0' }};border-radius:9px;font-family:inherit;font-size:.9rem;color:#1e293b;background:#f8fafc;outline:none;transition:border .2s;"
                       onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='{{ $errors->has('name') ? '#f87171' : '#e2e8f0' }}'">
                @error('name')
                    <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                @enderror
            </div>

            {{-- Phone + Email --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div>
                    <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">رقم الهاتف</label>
                    <input id="phone" name="phone" type="tel"
                           value="{{ old('phone') }}"
                           placeholder="+966 5x xxx xxxx"
                           style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;"
                           onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('phone')
                        <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email') }}"
                           placeholder="supplier@domain.com"
                           style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;"
                           onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#e2e8f0'">
                    @error('email')
                        <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Address --}}
            <div>
                <label style="display:block;font-size:.82rem;color:#374151;font-weight:600;margin-bottom:.4rem;">العنوان</label>
                <textarea id="address" name="address" rows="3"
                          placeholder="المدينة، الحي، الشارع..."
                          style="width:100%;padding:.7rem .9rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:.875rem;color:#1e293b;background:#f8fafc;outline:none;resize:vertical;"
                          onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#e2e8f0'">{{ old('address') }}</textarea>
                @error('address')
                    <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                @enderror
            </div>

        </div>
    </div>

    {{-- ══ Sidebar ═════════════════════════════════════════════════════════════ --}}
    <div style="display:flex;flex-direction:column;gap:1rem;">

        {{-- Opening Balance --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);overflow:hidden;">
            <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.6rem;">
                <div style="width:30px;height:30px;border-radius:8px;background:#fef9c3;display:flex;align-items:center;justify-content:center;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#854d0e" stroke-width="2">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                    </svg>
                </div>
                <span style="font-weight:700;color:#0f172a;font-size:.95rem;">الرصيد الافتتاحي</span>
            </div>
            <div style="padding:1.25rem;">
                <div style="position:relative;">
                    <input id="opening_balance" name="opening_balance" type="number"
                           step="0.01" min="0"
                           value="{{ old('opening_balance', '0.00') }}"
                           style="width:100%;padding:.7rem .9rem;padding-left:3rem;border:1.5px solid #e2e8f0;border-radius:9px;font-family:inherit;font-size:1rem;font-weight:600;color:#1e293b;background:#f8fafc;outline:none;text-align:left;direction:ltr;"
                           onfocus="this.style.borderColor='#059669'" onblur="this.style.borderColor='#e2e8f0'">
                    <span style="position:absolute;left:.8rem;top:50%;transform:translateY(-50%);font-size:.8rem;color:#94a3b8;font-weight:600;">ر.س</span>
                </div>
                <p style="font-size:.78rem;color:#94a3b8;margin-top:.5rem;">
                    المبلغ المستحق للمورد قبل بدء الاستخدام — اتركه 0 إذا لم يكن هناك رصيد
                </p>
                @error('opening_balance')
                    <p style="font-size:.78rem;color:#dc2626;margin-top:.3rem;">{{ $message }}</p>
                @enderror
            </div>
        </div>

        {{-- Info Box --}}
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1rem 1.1rem;display:flex;gap:.75rem;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2" style="flex-shrink:0;margin-top:.1rem;">
                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div style="font-size:.8rem;color:#14532d;line-height:1.6;">
                بعد الحفظ يمكنك إضافة فواتير شراء ومدفوعات وعرض كشف حساب المورد الكامل.
            </div>
        </div>

        {{-- Actions --}}
        <div style="display:flex;flex-direction:column;gap:.6rem;">
            <button type="submit"
                    style="width:100%;padding:.85rem;background:linear-gradient(135deg,#065f46,#059669);color:#fff;border:none;border-radius:10px;font-family:inherit;font-size:.95rem;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(5,150,105,.3);">
                حفظ المورد
            </button>
            <a href="{{ route('accounting.vendors.index') }}"
               style="display:block;text-align:center;padding:.75rem;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-size:.9rem;font-weight:600;text-decoration:none;">
                إلغاء
            </a>
        </div>

    </div>

</div>

</form>

@endsection
