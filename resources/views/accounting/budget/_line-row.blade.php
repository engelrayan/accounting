<div class="ac-line-row" style="display:grid;grid-template-columns:2fr 1fr 1fr auto;gap:.75rem;align-items:end;margin-bottom:.75rem;">
    <div class="ac-form-group" style="margin:0;">
        <label class="ac-label">الحساب</label>
        <select name="lines[{{ $index }}][account_id]" class="ac-select">
            @foreach($accounts as $acc)
                <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="ac-form-group" style="margin:0;">
        <label class="ac-label">الشهر</label>
        <select name="lines[{{ $index }}][month]" class="ac-select">
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}">{{ \App\Modules\Accounting\Models\BudgetLine::monthName($m) }}</option>
            @endfor
        </select>
    </div>
    <div class="ac-form-group" style="margin:0;">
        <label class="ac-label">المبلغ</label>
        <input type="number" name="lines[{{ $index }}][amount]"
               class="ac-input" min="0" step="0.01" placeholder="0.00">
    </div>
    <div style="padding-bottom:2px;">
        @if($index > 0)
        <button type="button" class="ac-btn ac-btn--danger ac-btn--sm"
                onclick="this.closest('.ac-line-row').remove()">×</button>
        @else
        <div style="width:32px;"></div>
        @endif
    </div>
</div>
