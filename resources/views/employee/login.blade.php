<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الموظف — محاسب عام</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Cairo', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #0f172a;
            overflow: hidden;
        }

        /* ── Left panel — decorative ── */
        .panel-left {
            flex: 1;
            background: linear-gradient(145deg, #1e3a5f 0%, #1e40af 50%, #4f46e5 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        /* Decorative circles */
        .panel-left::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            top: -100px; left: -100px;
        }
        .panel-left::after {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.05);
            bottom: -80px; right: -80px;
        }

        .panel-left__inner { position: relative; z-index: 1; text-align: center; color: #fff; }

        .panel-left__logo {
            width: 72px; height: 72px;
            background: rgba(255,255,255,.15);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,.2);
        }

        .panel-left__logo svg { width: 38px; height: 38px; }

        .panel-left__title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: .75rem;
            line-height: 1.2;
        }

        .panel-left__sub {
            font-size: 1rem;
            opacity: .75;
            line-height: 1.7;
            max-width: 280px;
        }

        .panel-left__features {
            margin-top: 2.5rem;
            display: flex;
            flex-direction: column;
            gap: .85rem;
            text-align: right;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: .75rem;
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px;
            padding: .65rem 1rem;
            backdrop-filter: blur(6px);
        }

        .feature-item svg { width: 18px; height: 18px; opacity: .9; flex-shrink: 0; }
        .feature-item span { font-size: .875rem; opacity: .9; }

        /* ── Right panel — form ── */
        .panel-right {
            width: 460px;
            flex-shrink: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 2.5rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .form-header__avatar {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #dbeafe, #ede9fe);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
        }

        .form-header__avatar svg { width: 28px; height: 28px; color: #4f46e5; }

        .form-header__title {
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: .35rem;
        }

        .form-header__sub {
            font-size: .875rem;
            color: #64748b;
        }

        /* Error alert */
        .alert-error {
            width: 100%;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 10px;
            padding: .75rem 1rem;
            font-size: .875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        /* Form */
        form { width: 100%; }

        .field { margin-bottom: 1.1rem; }

        .field label {
            display: block;
            font-size: .875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: .45rem;
        }

        .input-wrap { position: relative; }

        .input-wrap svg {
            position: absolute;
            top: 50%; left: 12px;
            transform: translateY(-50%);
            width: 18px; height: 18px;
            color: #9ca3af;
            pointer-events: none;
        }

        .input-wrap input {
            width: 100%;
            padding: .75rem .9rem .75rem 2.5rem;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: .9rem;
            color: #0f172a;
            background: #f8fafc;
            transition: border-color .2s, box-shadow .2s, background .2s;
            outline: none;
        }

        .input-wrap input:focus {
            border-color: #4f46e5;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79,70,229,.1);
        }

        .input-wrap input.error { border-color: #f87171; }

        .field-error {
            font-size: .78rem;
            color: #ef4444;
            margin-top: .3rem;
        }

        /* Remember me */
        .remember {
            display: flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.5rem;
        }

        .remember input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #4f46e5;
            cursor: pointer;
        }

        .remember label {
            font-size: .875rem;
            color: #64748b;
            cursor: pointer;
        }

        /* Submit btn */
        .btn-submit {
            width: 100%;
            padding: .85rem;
            background: linear-gradient(135deg, #4f46e5, #6366f1);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity .2s, transform .1s;
            box-shadow: 0 4px 14px rgba(79,70,229,.35);
        }

        .btn-submit:hover  { opacity: .92; }
        .btn-submit:active { transform: scale(.98); }

        /* Footer note */
        .form-footer {
            margin-top: 1.75rem;
            text-align: center;
            font-size: .8rem;
            color: #94a3b8;
            line-height: 1.6;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; overflow: auto; }
            .panel-left { padding: 2rem; min-height: 200px; }
            .panel-left__features { display: none; }
            .panel-right { width: 100%; padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>

{{-- ═══════════ Left / Decorative ═══════════ --}}
<div class="panel-left">
    <div class="panel-left__inner">

        <div class="panel-left__logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                <path d="M2 17l10 5 10-5"/>
                <path d="M2 12l10 5 10-5"/>
            </svg>
        </div>

        <div class="panel-left__title">محاسب عام</div>
        <div class="panel-left__sub">بوابة الموظفين — اطّلع على بياناتك وطلبات إجازاتك بسهولة</div>

        <div class="panel-left__features">
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span>عرض الملف الشخصي ومعلومات الراتب</span>
            </div>
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                    <path d="M9 16l2 2 4-4"/>
                </svg>
                <span>تقديم طلبات الإجازة ومتابعة حالتها</span>
            </div>
            <div class="feature-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
                <span>الاطلاع على تفاصيل الراتب والمستحقات</span>
            </div>
        </div>

    </div>
</div>

{{-- ═══════════ Right / Form ═══════════ --}}
<div class="panel-right">

    <div class="form-header">
        <div class="form-header__avatar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <div class="form-header__title">تسجيل الدخول</div>
        <div class="form-header__sub">مرحباً بك في بوابة الموظفين</div>
    </div>

    @if($errors->any())
    <div class="alert-error">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:18px;height:18px;flex-shrink:0;">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
        </svg>
        {{ $errors->first() }}
    </div>
    @endif

    <form method="POST" action="{{ route('employee.login') }}">
        @csrf

        <div class="field">
            <label for="phone">رقم الجوال أو رقم الموظف</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <input type="text" id="phone" name="phone"
                       value="{{ old('phone') }}"
                       class="{{ $errors->has('phone') ? 'error' : '' }}"
                       placeholder="05xxxxxxxx أو EMP-0001"
                       dir="ltr" autofocus>
            </div>
            @error('phone')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="field">
            <label for="password">كلمة المرور</label>
            <div class="input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                </svg>
                <input type="password" id="password" name="password"
                       class="{{ $errors->has('password') ? 'error' : '' }}"
                       placeholder="••••••••">
            </div>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="remember">
            <input type="checkbox" id="remember" name="remember" value="1">
            <label for="remember">تذكّرني</label>
        </div>

        <button type="submit" class="btn-submit">تسجيل الدخول</button>
    </form>

    <div class="form-footer">
        كلمة المرور الافتراضية هي رقم جوالك<br>
        في حال نسيت كلمة المرور، تواصل مع الإدارة
    </div>

</div>

</body>
</html>
