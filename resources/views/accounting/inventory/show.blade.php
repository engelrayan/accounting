@extends('accounting._layout')

@section('title', 'مخزون: ' . $product->name)

@section('topbar-actions')
    <div style="display:flex;gap:8px">
        <a href="{{ route('accounting.inventory.index') }}" class="ac-btn ac-btn--secondary">رجوع</a>
    </div>
@endsection

@section('content')

@include('accounting._flash')

@if($errors->has('adjust'))
    <div class="ac-alert ac-alert--error">{{ $errors->first('adjust') }}</div>
@endif

{{-- ── بطاقات الملخص ──────────────────────────────────────────────────── --}}
@php
    $qty   = $inventoryItem ? (float)$inventoryItem->quantity_on_hand : 0;
    $avg   = $inventoryItem ? (float)$inventoryItem->average_cost : 0;
    $value = round($qty * $avg, 2);
    $isLow = $inventoryItem && $inventoryItem->reorder_level && $qty <= (float)$inventoryItem->reorder_level;
@endphp

<div class="ac-dash-grid">

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">الكمية المتاحة</span>
            <div class="ac-dash-card__icon {{ $qty <= 0 ? 'ac-dash-card__icon--red' : ($isLow ? 'ac-dash-card__icon--amber' : 'ac-dash-card__icon--green') }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($qty, 3) }}</div>
        <div class="ac-dash-card__footer">{{ $product->unit ?? 'وحدة' }}</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">متوسط التكلفة</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--blue">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($avg, 4) }}</div>
        <div class="ac-dash-card__footer">تكلفة الوحدة (متوسط مرجح)</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">القيمة الإجمالية</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--green">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2"/>
                    <path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">{{ number_format($value, 2) }}</div>
        <div class="ac-dash-card__footer">الكمية × متوسط التكلفة</div>
    </div>

    <div class="ac-dash-card">
        <div class="ac-dash-card__header">
            <span class="ac-dash-card__label">حد إعادة الطلب</span>
            <div class="ac-dash-card__icon ac-dash-card__icon--amber">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
        </div>
        <div class="ac-dash-card__amount">
            {{ $inventoryItem?->reorder_level ? number_format((float)$inventoryItem->reorder_level, 3) : '—' }}
        </div>
        <div class="ac-dash-card__footer">{{ $isLow ? 'تنبيه: الكمية دون الحد!' : 'الحد الأدنى للطلب' }}</div>
    </div>

</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;margin-top:4px">

    {{-- ── حركات الصنف ──────────────────────────────────────────────────── --}}
    <div>
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
            <h2 style="font-size:1rem;font-weight:700;margin:0">سجل الحركات</h2>
            <a href="{{ route('accounting.inventory.movements', ['product_id' => $product->id]) }}"
               class="ac-btn ac-btn--ghost ac-btn--sm">عرض الكل</a>
        </div>

        @if($movements->isEmpty())
            <div class="ac-empty" style="padding:32px">
                <p style="margin:0;color:var(--ac-text-muted)">لا توجد حركات مسجّلة لهذا الصنف.</p>
            </div>
        @else
            <div class="ac-card">
                <div style="overflow-x:auto">
                    <table class="ac-table">
                        <thead>
                            <tr>
                                <th>التاريخ</th>
                                <th style="text-align:center">النوع</th>
                                <th style="text-align:left">الكمية</th>
                                <th style="text-align:left">تكلفة الوحدة</th>
                                <th style="text-align:left">الإجمالي</th>
                                <th>ملاحظات / مرجع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($movements as $mov)
                            <tr>
                                <td style="white-space:nowrap;color:var(--ac-text-muted);font-size:.82rem">
                                    {{ $mov->created_at->format('Y-m-d') }}
                                </td>
                                <td style="text-align:center">
                                    <span class="ac-badge {{ $mov->typeBadgeClass() }}">{{ $mov->typeLabel() }}</span>
                                </td>
                                <td style="text-align:left;font-variant-numeric:tabular-nums;font-weight:600;
                                           color:{{ (float)$mov->quantity >= 0 ? 'var(--ac-success)' : 'var(--ac-danger)' }}">
                                    {{ (float)$mov->quantity >= 0 ? '+' : '' }}{{ number_format((float)$mov->quantity, 3) }}
                                </td>
                                <td style="text-align:left;font-variant-numeric:tabular-nums;color:var(--ac-text-muted)">
                                    {{ number_format((float)$mov->unit_cost, 4) }}
                                </td>
                                <td style="text-align:left;font-variant-numeric:tabular-nums">
                                    {{ number_format((float)$mov->total_cost, 2) }}
                                </td>
                                <td style="font-size:.82rem;color:var(--ac-text-muted);max-width:180px">
                                    @if($mov->reference_type && $mov->reference_id)
                                        {{ $mov->reference_type }} #{{ $mov->reference_id }}
                                    @elseif($mov->notes)
                                        {{ $mov->notes }}
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="margin-top:12px">{{ $movements->links() }}</div>
        @endif
    </div>

    {{-- ── تسوية يدوية ───────────────────────────────────────────────────── --}}
    @can('can-write')
    <div>
        <h2 style="font-size:1rem;font-weight:700;margin:0 0 12px">تسوية يدوية</h2>
        <div class="ac-card">
            <div class="ac-card__body">
                <form method="POST" action="{{ route('accounting.inventory.adjust', $product) }}">
                    @csrf

                    <div class="ac-form-group" style="margin-bottom:14px">
                        <label class="ac-label ac-label--required" for="adj_qty">الكمية</label>
                        <input id="adj_qty" name="quantity" type="number" step="0.001"
                               class="ac-control {{ $errors->has('quantity') ? 'ac-control--error' : '' }}"
                               placeholder="موجب = إضافة، سالب = خصم"
                               value="{{ old('quantity') }}" required>
                        <div style="font-size:.76rem;color:var(--ac-text-muted);margin-top:4px">
                            مثال: 5 لإضافة، ‑3 لخصم
                        </div>
                        @error('quantity') <span class="ac-field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="ac-form-group" style="margin-bottom:14px">
                        <label class="ac-label" for="adj_cost">تكلفة الوحدة</label>
                        <input id="adj_cost" name="unit_cost" type="number" step="0.0001" min="0"
                               class="ac-control"
                               placeholder="{{ number_format($avg, 4) }} (افتراضي: المتوسط)"
                               value="{{ old('unit_cost') }}">
                    </div>

                    <div class="ac-form-group" style="margin-bottom:16px">
                        <label class="ac-label" for="adj_notes">ملاحظات</label>
                        <textarea id="adj_notes" name="notes" rows="3"
                                  class="ac-control"
                                  placeholder="سبب التسوية...">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="ac-btn ac-btn--primary" style="width:100%">
                        تسجيل التسوية
                    </button>
                </form>
            </div>
        </div>

        {{-- معلومات المنتج --}}
        <div class="ac-card" style="margin-top:14px">
            <div class="ac-card__body" style="font-size:.85rem">
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ac-border)">
                    <span style="color:var(--ac-text-muted)">سعر البيع</span>
                    <span style="font-weight:600">{{ number_format($product->sale_price, 2) }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ac-border)">
                    <span style="color:var(--ac-text-muted)">سعر الشراء</span>
                    <span>{{ $product->purchase_price ? number_format($product->purchase_price, 2) : '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--ac-border)">
                    <span style="color:var(--ac-text-muted)">الوحدة</span>
                    <span>{{ $product->unit ?? '—' }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding:6px 0">
                    <span style="color:var(--ac-text-muted)">المستودع</span>
                    <span>{{ $warehouse->name }}</span>
                </div>
            </div>
        </div>
    </div>
    @endcan

</div>

@endsection
