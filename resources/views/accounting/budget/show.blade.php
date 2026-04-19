@extends('accounting._layout')
@section('title', 'الميزانية: ' . $budget->name)

@section('topbar-actions')
<div style="display:flex;gap:.75rem;align-items:center;">
    @if($budget->isDraft())
        <form method="POST" action="{{ route('accounting.budget.activate', $budget) }}">
            @csrf
            <button type="submit" class="ac-btn ac-btn--success">تفعيل الميزانية</button>
        </form>
    @elseif($budget->isActive())
        <form method="POST" action="{{ route('accounting.budget.close', $budget) }}"
              onsubmit="return confirm('إغلاق الميزانية؟')">
            @csrf
            <button type="submit" class="ac-btn ac-btn--secondary">إغلاق الميزانية</button>
        </form>
    @endif
    <span class="ac-badge {{ $budget->statusBadgeClass() }}" style="font-size:.9rem;padding:.4rem .9rem;">
        {{ $budget->statusLabel() }}
    </span>
</div>
@endsection

@section('content')

{{-- Tabs --}}
<div class="ac-tabs" style="margin-bottom:1.5rem;">
    <button class="ac-tab ac-tab--active" onclick="switchTab(event,'tab-lines')">بنود الميزانية</button>
    <button class="ac-tab" onclick="switchTab(event,'tab-compare')">مقارنة بالفعلي</button>
    @if($budget->isDraft())
    <button class="ac-tab" onclick="switchTab(event,'tab-add')">إضافة بنود</button>
    @endif
</div>

{{-- ═══════════ Tab 1: Lines ═══════════ --}}
<div id="tab-lines">
    @if($budget->lines->isEmpty())
        <div class="ac-card ac-empty">لم تُضَف بنود بعد — استخدم تبويب "إضافة بنود"</div>
    @else
        @php
            $grouped = $budget->lines->groupBy('account_id');
        @endphp

        <div class="ac-card" style="overflow-x:auto;">
            <table class="ac-table" style="min-width:900px;">
                <thead>
                    <tr>
                        <th>الحساب</th>
                        @for($m=1;$m<=12;$m++)
                            <th style="text-align:center;font-size:.8rem;">{{ \App\Modules\Accounting\Models\BudgetLine::monthName($m) }}</th>
                        @endfor
                        <th style="text-align:left;">المجموع</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grouped as $accountId => $lines)
                    @php
                        $account  = $lines->first()->account;
                        $byMonth  = $lines->pluck('amount','period_month');
                        $rowTotal = $lines->sum('amount');
                    @endphp
                    <tr>
                        <td>
                            <span style="font-size:.8rem;color:var(--ac-muted);">{{ $account->code }}</span>
                            {{ $account->name }}
                        </td>
                        @for($m=1;$m<=12;$m++)
                            <td style="text-align:center;font-size:.85rem;">
                                {{ $byMonth[$m] ? number_format($byMonth[$m],0) : '—' }}
                            </td>
                        @endfor
                        <td style="font-weight:700;text-align:left;">{{ number_format($rowTotal,2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--ac-bg);font-weight:700;">
                        <td>الإجمالي</td>
                        @php $monthTotals = []; @endphp
                        @for($m=1;$m<=12;$m++)
                            @php
                                $mt = $budget->lines->where('period_month',$m)->sum('amount');
                                $monthTotals[] = $mt;
                            @endphp
                            <td style="text-align:center;font-size:.85rem;">{{ $mt ? number_format($mt,0) : '—' }}</td>
                        @endfor
                        <td style="text-align:left;">{{ number_format(array_sum($monthTotals),2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>

{{-- ═══════════ Tab 2: Comparison ═══════════ --}}
<div id="tab-compare" style="display:none;">
    @if(empty($comparison))
        <div class="ac-card ac-empty">لا توجد بنود لمقارنتها بعد</div>
    @else
        <div class="ac-card" style="overflow-x:auto;">
            <table class="ac-table" style="min-width:700px;">
                <thead>
                    <tr>
                        <th>الحساب</th>
                        <th style="text-align:left;">الميزانية</th>
                        <th style="text-align:left;">الفعلي</th>
                        <th style="text-align:left;">الفرق</th>
                        <th style="text-align:left;">%</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparison as $row)
                    @php
                        $variance = $row['variance'];
                        $pct      = $row['total_budget'] > 0
                                  ? round(($row['total_actual'] / $row['total_budget']) * 100, 1)
                                  : 0;
                        $varClass = $variance >= 0 ? 'ac-text--success' : 'ac-text--danger';
                    @endphp
                    <tr>
                        <td>{{ $row['account']->name }}</td>
                        <td style="text-align:left;">{{ number_format($row['total_budget'],2) }}</td>
                        <td style="text-align:left;">{{ number_format($row['total_actual'],2) }}</td>
                        <td style="text-align:left;" class="{{ $varClass }}">
                            {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance,2) }}
                        </td>
                        <td style="text-align:left;">
                            <div style="display:flex;align-items:center;gap:.5rem;">
                                <div style="background:var(--ac-border);border-radius:4px;height:6px;width:80px;overflow:hidden;">
                                    <div style="background:{{ $pct <= 100 ? 'var(--ac-primary)' : 'var(--ac-danger)' }};height:100%;width:{{ min($pct,100) }}%;transition:width .3s;"></div>
                                </div>
                                <span style="font-size:.8rem;">{{ $pct }}%</span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ═══════════ Tab 3: Add Lines (draft only) ═══════════ --}}
@if($budget->isDraft())
<div id="tab-add" style="display:none;">
    <div class="ac-card">
        <div class="ac-card__header">
            <h3 class="ac-card__title">إضافة بنود الميزانية</h3>
        </div>
        <p style="color:var(--ac-muted);font-size:.875rem;margin-bottom:1.5rem;">
            أدخل مبلغ الميزانية لكل حساب ولكل شهر. يمكنك إضافة عدة أسطر دفعة واحدة.
        </p>

        <form method="POST" action="{{ route('accounting.budget.add-lines', $budget) }}" id="linesForm">
            @csrf

            <div id="linesContainer">
                @include('accounting.budget._line-row', ['index' => 0, 'accounts' => $accounts])
            </div>

            <button type="button" class="ac-btn ac-btn--ghost ac-btn--sm" style="margin-top:1rem;" onclick="addLine()">
                + إضافة سطر آخر
            </button>

            <div style="margin-top:1.5rem;display:flex;gap:1rem;">
                <button type="submit" class="ac-btn ac-btn--primary">حفظ البنود</button>
            </div>
        </form>
    </div>
</div>
@endif

@push('scripts')
<script>
function switchTab(e, id) {
    document.querySelectorAll('.ac-tab').forEach(t => t.classList.remove('ac-tab--active'));
    document.querySelectorAll('[id^="tab-"]').forEach(t => t.style.display = 'none');
    e.target.classList.add('ac-tab--active');
    document.getElementById(id).style.display = 'block';
}

let lineCount = 1;
const accountOptions = `{!! $accounts->map(fn($a) => '<option value="'.$a->id.'">'.$a->code.' - '.$a->name.'</option>')->implode('') !!}`;
const monthOptions   = `{!! collect(range(1,12))->map(fn($m) => '<option value="'.$m.'">'.\App\Modules\Accounting\Models\BudgetLine::monthName($m).'</option>')->implode('') !!}`;

function addLine() {
    const div = document.createElement('div');
    div.innerHTML = buildRow(lineCount++);
    document.getElementById('linesContainer').appendChild(div.firstElementChild);
}

function buildRow(i) {
    return `<div class="ac-line-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.75rem;align-items:end;margin-bottom:.75rem;">
        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">الحساب</label>
            <select name="lines[${i}][account_id]" class="ac-select">${accountOptions}</select>
        </div>
        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">الشهر</label>
            <select name="lines[${i}][month]" class="ac-select">${monthOptions}</select>
        </div>
        <div class="ac-form-group" style="margin:0;">
            <label class="ac-label">المبلغ</label>
            <input type="number" name="lines[${i}][amount]" class="ac-input" min="0" step="0.01" placeholder="0.00">
        </div>
        <div style="padding-bottom:2px;">
            <button type="button" class="ac-btn ac-btn--danger ac-btn--sm" onclick="this.closest('.ac-line-row').remove()">×</button>
        </div>
    </div>`;
}
</script>
@endpush

@endsection
