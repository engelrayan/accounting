@extends('accounting._layout')

@section('title', 'تخصيص القائمة')

@section('content')
<div class="ac-page-header">
    <div>
        <h1 class="ac-page-header__title">تخصيص القائمة</h1>
        <p class="ac-page-header__subtitle">غيّر أسماء الأقسام أو عطّل أي موديول لا تستخدمه شركتك.</p>
    </div>

    <form method="POST" action="{{ route('accounting.settings.modules.reset') }}" data-confirm="إعادة كل أسماء وحالات الموديولات للوضع الافتراضي؟">
        @csrf
        @method('DELETE')
        <button type="submit" class="ac-btn ac-btn--secondary">
            إعادة الافتراضي
        </button>
    </form>
</div>

<form method="POST" action="{{ route('accounting.settings.modules.update') }}">
    @csrf
    @method('PATCH')

    <div class="ac-modules-settings">
        @foreach($modules as $module)
            <section class="ac-module-card {{ $module['is_enabled'] ? '' : 'ac-module-card--disabled' }}">
                <div class="ac-module-card__icon">
                    @include('accounting.partials.module-icon', ['icon' => $module['icon']])
                </div>

                <div class="ac-module-card__body">
                    <div class="ac-module-card__head">
                        <div>
                            <h2>{{ $module['label'] }}</h2>
                            <span>{{ $module['key'] }}</span>
                        </div>

                        <label class="ac-toggle" title="تفعيل أو تعطيل الموديول">
                            <input type="hidden" name="modules[{{ $module['key'] }}][is_enabled]" value="0">
                            <input type="checkbox"
                                   name="modules[{{ $module['key'] }}][is_enabled]"
                                   value="1"
                                   {{ $module['is_enabled'] ? 'checked' : '' }}>
                            <span class="ac-toggle__track"></span>
                        </label>
                    </div>

                    <input type="hidden" name="modules[{{ $module['key'] }}][key]" value="{{ $module['key'] }}">

                    <div class="ac-form-group">
                        <label class="ac-label" for="module-label-{{ $module['key'] }}">اسم القسم في القائمة</label>
                        <input type="text"
                               id="module-label-{{ $module['key'] }}"
                               name="modules[{{ $module['key'] }}][label]"
                               class="ac-control"
                               value="{{ old('modules.' . $module['key'] . '.label', $module['custom_label']) }}"
                               placeholder="{{ $module['default_label'] }}">
                        <span class="ac-field-hint">اتركه فارغًا لاستخدام الاسم الافتراضي: {{ $module['default_label'] }}</span>
                    </div>
                </div>
            </section>
        @endforeach
    </div>

    <div class="ac-settings-actions">
        <button type="submit" class="ac-btn ac-btn--primary">حفظ تخصيص القائمة</button>
    </div>
</form>
@endsection
