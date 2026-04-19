@extends('accounting._layout')

@section('title', 'معاملة جديدة')

@section('content')
<div class="ac-page-header">
    <h1 class="ac-page-header__title">تسجيل معاملة جديدة</h1>
    <a href="{{ route('accounting.transactions.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
</div>

@if($errors->has('transaction'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('transaction') }}</div>
@endif

<div class="ac-card">
    <div class="ac-card__body">
        <form method="POST" action="{{ route('accounting.transactions.store') }}" id="txn-form" enctype="multipart/form-data">
            @csrf

            {{-- ── نوع العملية ──────────────────────────────────────────── --}}
            <div class="ac-form-group">
                <label class="ac-label ac-label--required" for="txn-type">نوع العملية</label>
                <div class="ac-type-grid" id="txn-type-grid">

                    <label class="ac-type-card {{ old('type') === 'expense' ? 'ac-type-card--active' : '' }}" data-type="expense">
                        <input type="radio" name="type" value="expense" {{ old('type') === 'expense' ? 'checked' : '' }}>
                        <span class="ac-type-card__icon ac-type-card__icon--red">💸</span>
                        <span class="ac-type-card__label">مصروف</span>
                        <span class="ac-type-card__sub">دفع من الخزنة</span>
                    </label>

                    <label class="ac-type-card {{ old('type') === 'income' ? 'ac-type-card--active' : '' }}" data-type="income">
                        <input type="radio" name="type" value="income" {{ old('type') === 'income' ? 'checked' : '' }}>
                        <span class="ac-type-card__icon ac-type-card__icon--green">💰</span>
                        <span class="ac-type-card__label">إيراد</span>
                        <span class="ac-type-card__sub">استلام أموال</span>
                    </label>

                    <label class="ac-type-card {{ old('type') === 'transfer' ? 'ac-type-card--active' : '' }}" data-type="transfer">
                        <input type="radio" name="type" value="transfer" {{ old('type') === 'transfer' ? 'checked' : '' }}>
                        <span class="ac-type-card__icon ac-type-card__icon--blue">🔄</span>
                        <span class="ac-type-card__label">تحويل</span>
                        <span class="ac-type-card__sub">بين الحسابات</span>
                    </label>

                    <label class="ac-type-card {{ old('type') === 'capital_addition' ? 'ac-type-card--active' : '' }}" data-type="capital_addition">
                        <input type="radio" name="type" value="capital_addition" {{ old('type') === 'capital_addition' ? 'checked' : '' }}>
                        <span class="ac-type-card__icon ac-type-card__icon--purple">📈</span>
                        <span class="ac-type-card__label">إضافة رأس مال</span>
                        <span class="ac-type-card__sub">من شريك</span>
                    </label>

                    <label class="ac-type-card {{ old('type') === 'withdrawal' ? 'ac-type-card--active' : '' }}" data-type="withdrawal">
                        <input type="radio" name="type" value="withdrawal" {{ old('type') === 'withdrawal' ? 'checked' : '' }}>
                        <span class="ac-type-card__icon ac-type-card__icon--amber">📤</span>
                        <span class="ac-type-card__label">سحب</span>
                        <span class="ac-type-card__sub">سحب شريك</span>
                    </label>

                </div>
                @error('type') <span class="ac-field-error">{{ $message }}</span> @enderror
            </div>

            {{-- ── الحقول الديناميكية (مخفية حتى يختار المستخدم النوع) ──── --}}
            <div id="txn-fields" style="display:none;">
                <hr class="ac-divider">

                {{-- مصروف ────────────────────────────────────────────────── --}}
                <div id="fields-expense" class="txn-fields-panel" style="display:none;">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="expense_cash_account_id">من (الخزنة / البنك)</label>
                            <select id="expense_cash_account_id" name="cash_account_id"
                                    class="ac-select {{ $errors->has('cash_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('cash_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="expense_account_id">على (نوع المصروف)</label>
                            <select id="expense_account_id" name="expense_account_id"
                                    class="ac-select {{ $errors->has('expense_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($expenseAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('expense_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('expense_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- إيراد ─────────────────────────────────────────────────── --}}
                <div id="fields-income" class="txn-fields-panel" style="display:none;">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="income_revenue_account_id">نوع الإيراد</label>
                            <select id="income_revenue_account_id" name="revenue_account_id"
                                    class="ac-select {{ $errors->has('revenue_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($revenueAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('revenue_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('revenue_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="income_cash_account_id">طريقة الاستلام</label>
                            <select id="income_cash_account_id" name="cash_account_id"
                                    class="ac-select {{ $errors->has('cash_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('cash_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- تحويل ─────────────────────────────────────────────────── --}}
                <div id="fields-transfer" class="txn-fields-panel" style="display:none;">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="transfer_from_account_id">من حساب</label>
                            <select id="transfer_from_account_id" name="from_account_id"
                                    class="ac-select {{ $errors->has('from_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($allAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('from_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('from_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="transfer_to_account_id">إلى حساب</label>
                            <select id="transfer_to_account_id" name="to_account_id"
                                    class="ac-select {{ $errors->has('to_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($allAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('to_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->code }} — {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('to_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- إضافة رأس مال ────────────────────────────────────────── --}}
                <div id="fields-capital_addition" class="txn-fields-panel" style="display:none;">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="capital_partner_id">الشريك</label>
                            <select id="capital_partner_id" name="partner_id"
                                    class="ac-select {{ $errors->has('partner_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر الشريك —</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('partner_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="capital_cash_account_id">الخزنة / البنك</label>
                            <select id="capital_cash_account_id" name="cash_account_id"
                                    class="ac-select {{ $errors->has('cash_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('cash_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- سحب ─────────────────────────────────────────────────── --}}
                <div id="fields-withdrawal" class="txn-fields-panel" style="display:none;">
                    <div class="ac-form-row">
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="withdrawal_partner_id">الشريك</label>
                            <select id="withdrawal_partner_id" name="partner_id"
                                    class="ac-select {{ $errors->has('partner_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر الشريك —</option>
                                @foreach($partners as $p)
                                    <option value="{{ $p->id }}" {{ old('partner_id') == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('partner_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="ac-form-group">
                            <label class="ac-label ac-label--required" for="withdrawal_cash_account_id">من (الخزنة / البنك)</label>
                            <select id="withdrawal_cash_account_id" name="cash_account_id"
                                    class="ac-select {{ $errors->has('cash_account_id') ? 'ac-select--error' : '' }}">
                                <option value="">— اختر —</option>
                                @foreach($cashAccounts as $acc)
                                    <option value="{{ $acc->id }}" {{ old('cash_account_id') == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('cash_account_id') <span class="ac-field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                {{-- ── المبلغ والتاريخ والوصف (مشتركة لكل الأنواع) ──────── --}}
                <div class="ac-form-row">
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="amount">المبلغ</label>
                        <input id="amount" name="amount" type="number" step="0.01" min="0.01"
                               class="ac-control ac-control--num {{ $errors->has('amount') ? 'ac-control--error' : '' }}"
                               value="{{ old('amount') }}" placeholder="0.00">
                        @error('amount') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="ac-form-group">
                        <label class="ac-label ac-label--required" for="transaction_date">التاريخ</label>
                        <input id="transaction_date" name="transaction_date" type="date"
                               class="ac-control {{ $errors->has('transaction_date') ? 'ac-control--error' : '' }}"
                               value="{{ old('transaction_date', date('Y-m-d')) }}">
                        @error('transaction_date') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="ac-form-group">
                    <label class="ac-label" for="description">الوصف (اختياري)</label>
                    <input id="description" name="description" type="text"
                           class="ac-control"
                           value="{{ old('description') }}"
                           placeholder="مثال: فاتورة كهرباء يناير 2026">
                </div>

                {{-- ── المرفقات ─────────────────────────────────────────────── --}}
                <div class="ac-form-group">
                    <label class="ac-label" for="attachments">مرفقات (اختياري)</label>

                    <div class="ac-upload-zone" id="upload-zone">
                        <input type="file"
                               id="attachments"
                               name="attachments[]"
                               class="ac-upload-zone__input"
                               multiple
                               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.xlsx,.xls,.csv">

                        <div class="ac-upload-zone__body" id="upload-placeholder">
                            <span class="ac-upload-zone__icon">📎</span>
                            <span class="ac-upload-zone__text">اسحب الملفات هنا أو <span class="ac-upload-zone__browse">اختر من جهازك</span></span>
                            <span class="ac-upload-zone__hint">صور · PDF · Excel · CSV — حد أقصى 5 ميجابايت للملف · حتى 10 ملفات</span>
                        </div>
                    </div>

                    {{-- قائمة الملفات المختارة (تُملأ بـ JS) --}}
                    <ul class="ac-upload-list" id="upload-list" hidden></ul>

                    @error('attachments')   <span class="ac-field-error">{{ $message }}</span> @enderror
                    @error('attachments.*') <span class="ac-field-error">{{ $message }}</span> @enderror
                </div>

                <div class="ac-form-actions">
                    <button type="submit" class="ac-btn ac-btn--primary">تسجيل المعاملة</button>
                    <a href="{{ route('accounting.transactions.index') }}" class="ac-btn ac-btn--secondary">إلغاء</a>
                </div>

            </div>{{-- #txn-fields --}}

        </form>
    </div>
</div>
@endsection
