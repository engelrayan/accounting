@extends('accounting._layout')

@section('title', 'المحافظات')

@section('content')

@include('accounting._flash')

<div class="ac-page-header">
    <h1 class="ac-page-header__title">إدارة المحافظات</h1>
    <a href="{{ route('accounting.price-lists.index') }}" class="ac-btn ac-btn--secondary">قوائم الأسعار</a>
</div>

{{-- ── Summary Cards ──────────────────────────────────────────────────────── --}}
<div class="ac-dash-grid" style="grid-template-columns: repeat(3, 1fr)">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">إجمالي المحافظات</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s-8-4.5-8-11.8A8 8 0 0112 2a8 8 0 018 8.2c0 7.3-8 11.8-8 11.8z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $totalCount }}</div>
        <div class="ac-dash-card__footer">{{ $activeCount }} نشطة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">افتراضية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $totalCount - $customCount }}</div>
        <div class="ac-dash-card__footer">من المحافظات الرسمية</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">مخصصة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="16"/>
                    <line x1="8" y1="12" x2="16" y2="12"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ $customCount }}</div>
        <div class="ac-dash-card__footer">مناطق إضافية</div>
    </div>

</div>

<div class="ac-gov-layout">

    {{-- ── Add New Governorate Form ──────────────────────────────────────── --}}
    @can('can-write')
    <div class="ac-card ac-card--compact ac-gov-add-card">
        <div class="ac-card__body">
            <p class="ac-section-label">إضافة محافظة / منطقة جديدة</p>

            @if($errors->has('name_ar'))
                <div class="ac-alert ac-alert--error" style="margin-bottom:.75rem">
                    {{ $errors->first('name_ar') }}
                </div>
            @endif
            @if($errors->has('delete'))
                <div class="ac-alert ac-alert--error" style="margin-bottom:.75rem">
                    {{ $errors->first('delete') }}
                </div>
            @endif

            <form method="POST" action="{{ route('accounting.governorates.store') }}"
                  class="ac-gov-add-form">
                @csrf
                <input type="text" name="name_ar"
                       class="ac-control ac-gov-add-form__input {{ $errors->has('name_ar') ? 'ac-control--error' : '' }}"
                       placeholder="مثال: العبور، العاشر من رمضان..."
                       value="{{ old('name_ar') }}"
                       required>
                <button type="submit" class="ac-btn ac-btn--primary">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    إضافة
                </button>
            </form>

            <p style="font-size:.78rem;color:var(--ac-text-muted);margin-top:.5rem">
                يمكنك إضافة مناطق أو مدن لا تشملها المحافظات الافتراضية (مثل: العبور، العاشر من رمضان، برج العرب...).
            </p>
        </div>
    </div>
    @endcan

    {{-- ── Governorates Table ─────────────────────────────────────────────── --}}
    <div class="ac-card">
        <div style="overflow-x:auto">
            <table class="ac-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th style="text-align:center">النوع</th>
                        <th style="text-align:center">الحالة</th>
                        @can('can-write')
                        <th></th>
                        @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach($governorates as $gov)
                    <tr class="{{ !$gov->is_active ? 'ac-table-row--muted' : '' }}">
                        <td style="color:var(--ac-text-muted);font-size:.82rem;width:40px">
                            {{ $gov->sort_order }}
                        </td>
                        <td style="font-weight:600">{{ $gov->name_ar }}</td>
                        <td style="text-align:center">
                            @if($gov->is_system)
                                <span class="ac-badge">افتراضية</span>
                            @else
                                <span class="ac-badge ac-badge--info">مخصصة</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($gov->is_active)
                                <span class="ac-badge ac-badge--success">نشطة</span>
                            @else
                                <span class="ac-badge ac-badge--muted">معطّلة</span>
                            @endif
                        </td>
                        @can('can-write')
                        <td>
                            <div style="display:flex;gap:6px;justify-content:flex-end">
                                <form method="POST"
                                      action="{{ route('accounting.governorates.toggle', $gov) }}"
                                      style="display:inline">
                                    @csrf
                                    <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                                        {{ $gov->is_active ? 'تعطيل' : 'تفعيل' }}
                                    </button>
                                </form>

                                @if(!$gov->is_system)
                                <form method="POST"
                                      action="{{ route('accounting.governorates.destroy', $gov) }}"
                                      style="display:inline"
                                      onsubmit="return confirm('حذف [{{ $gov->name_ar }}]؟')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="ac-btn ac-btn--ghost ac-btn--sm ac-btn--danger-ghost">
                                        حذف
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                        @endcan
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- .ac-gov-layout --}}

@endsection
