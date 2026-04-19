@extends('accounting._layout')

@section('title', 'عروض الأسعار')

@section('topbar-actions')
<a href="{{ route('accounting.quotations.create') }}" class="ac-btn ac-btn--primary">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
    </svg>
    + عرض سعر جديد
</a>
@endsection

@section('content')

@php
$fmt = fn(float $n) => number_format($n, 2);

$statusConfig = [
    'draft'    => ['label' => 'مسودة',   'dot' => '#94a3b8', 'bg' => '#f1f5f9', 'color' => '#475569'],
    'sent'     => ['label' => 'مرسل',    'dot' => '#3b82f6', 'bg' => '#eff6ff', 'color' => '#1d4ed8'],
    'accepted' => ['label' => 'مقبول',   'dot' => '#22c55e', 'bg' => '#f0fdf4', 'color' => '#15803d'],
    'rejected' => ['label' => 'مرفوض',   'dot' => '#ef4444', 'bg' => '#fef2f2', 'color' => '#b91c1c'],
    'expired'  => ['label' => 'منتهي',   'dot' => '#f59e0b', 'bg' => '#fffbeb', 'color' => '#b45309'],
    'invoiced' => ['label' => 'مُفوتَر', 'dot' => '#8b5cf6', 'bg' => '#f5f3ff', 'color' => '#6d28d9'],
];

$statusLabels = [
    ''         => 'جميع الحالات',
    'draft'    => 'مسودة',
    'sent'     => 'مرسل',
    'accepted' => 'مقبول',
    'rejected' => 'مرفوض',
    'expired'  => 'منتهي',
    'invoiced' => 'مُفوتَر',
];
@endphp

{{-- ══════════════════════════════════════════════════════
     STATS ROW
     ══════════════════════════════════════════════════════ --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">

    {{-- مسودة --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:13px;font-weight:600;color:#64748b;">مسودة</span>
            <div style="width:36px;height:36px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2">
                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                    <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                </svg>
            </div>
        </div>
        <div style="font-size:28px;font-weight:700;color:#1e293b;line-height:1;">{{ $draftCount }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:6px;">عرض سعر</div>
    </div>

    {{-- مرسل --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:13px;font-weight:600;color:#1d4ed8;">مرسل</span>
            <div style="width:36px;height:36px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
                    <line x1="22" y1="2" x2="11" y2="13"/>
                    <polygon points="22,2 15,22 11,13 2,9"/>
                </svg>
            </div>
        </div>
        <div style="font-size:28px;font-weight:700;color:#1e293b;line-height:1;">{{ $sentCount }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:6px;">عرض سعر</div>
    </div>

    {{-- مقبول --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:13px;font-weight:600;color:#15803d;">مقبول</span>
            <div style="width:36px;height:36px;border-radius:10px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2">
                    <polyline points="20,6 9,17 4,12"/>
                </svg>
            </div>
        </div>
        <div style="font-size:28px;font-weight:700;color:#1e293b;line-height:1;">{{ $acceptedCount }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:6px;">عرض سعر</div>
    </div>

    {{-- إجمالي القيمة --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:20px 22px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
            <span style="font-size:13px;font-weight:600;color:#6d28d9;">إجمالي القيمة</span>
            <div style="width:36px;height:36px;border-radius:10px;background:#f5f3ff;display:flex;align-items:center;justify-content:center;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2">
                    <line x1="12" y1="1" x2="12" y2="23"/>
                    <path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
                </svg>
            </div>
        </div>
        <div style="font-size:22px;font-weight:700;color:#1e293b;line-height:1;">{{ $fmt($totalValue) }}</div>
        <div style="font-size:12px;color:#94a3b8;margin-top:6px;">ريال سعودي</div>
    </div>

</div>

{{-- ══════════════════════════════════════════════════════
     FILTERS
     ══════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:16px 20px;margin-bottom:20px;">
    <form method="GET" action="{{ route('accounting.quotations.index') }}"
          style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">

        {{-- حالة العرض --}}
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">الحالة</label>
            <select name="status"
                    style="font-family:Cairo,sans-serif;font-size:13px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;background:#fff;min-width:150px;outline:none;cursor:pointer;">
                @foreach($statusLabels as $val => $label)
                    <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- العميل --}}
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">العميل</label>
            <select name="customer_id"
                    style="font-family:Cairo,sans-serif;font-size:13px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;background:#fff;min-width:200px;outline:none;cursor:pointer;">
                <option value="">— جميع العملاء —</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                        {{ $c->name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Search --}}
        <div style="display:flex;flex-direction:column;gap:4px;">
            <label style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">بحث</label>
            <input type="text" name="q" value="{{ request('q') }}"
                   placeholder="رقم العرض أو العميل..."
                   style="font-family:Cairo,sans-serif;font-size:13px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;color:#1e293b;width:200px;outline:none;">
        </div>

        {{-- Buttons --}}
        <div style="display:flex;align-items:flex-end;gap:8px;padding-top:20px;">
            <button type="submit"
                    style="font-family:Cairo,sans-serif;font-size:13px;font-weight:600;padding:8px 18px;background:#4f46e5;color:#fff;border:none;border-radius:8px;cursor:pointer;">
                بحث
            </button>
            <a href="{{ route('accounting.quotations.index') }}"
               style="font-family:Cairo,sans-serif;font-size:13px;font-weight:500;padding:8px 14px;background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;border-radius:8px;text-decoration:none;display:inline-block;">
                إعادة تعيين
            </a>
        </div>

    </form>
</div>

{{-- ══════════════════════════════════════════════════════
     TABLE CARD
     ══════════════════════════════════════════════════════ --}}
<div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">

    {{-- Card header --}}
    <div style="padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:15px;font-weight:700;color:#1e293b;">قائمة عروض الأسعار</span>
        @if($quotations->total() > 0)
        <span style="font-size:12px;color:#94a3b8;">{{ $quotations->total() }} عرض</span>
        @endif
    </div>

    @if($quotations->isEmpty())
        {{-- Empty state --}}
        <div style="padding:64px 24px;text-align:center;">
            <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1.2"
                 style="margin:0 auto 16px;">
                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                <polyline points="14,2 14,8 20,8"/>
                <line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/>
                <line x1="10" y1="9" x2="8" y2="9"/>
            </svg>
            <p style="font-size:15px;color:#64748b;margin:0 0 4px;font-weight:500;">لا توجد عروض أسعار</p>
            <p style="font-size:13px;color:#94a3b8;margin:0 0 20px;">أنشئ أول عرض سعر لعملائك</p>
            <a href="{{ route('accounting.quotations.create') }}"
               style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#4f46e5;color:#fff;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                عرض سعر جديد
            </a>
        </div>
    @else
        {{-- Table --}}
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">رقم العرض</th>
                        <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">العميل</th>
                        <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">التاريخ</th>
                        <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">صالح حتى</th>
                        <th style="padding:11px 16px;text-align:left;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">الإجمالي</th>
                        <th style="padding:11px 16px;text-align:right;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">الحالة</th>
                        <th style="padding:11px 16px;text-align:center;font-size:12px;font-weight:600;color:#64748b;border-bottom:1px solid #e2e8f0;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotations as $q)
                    @php
                        $sc = $statusConfig[$q->status] ?? $statusConfig['draft'];
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;transition:background .15s;"
                        onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">

                        {{-- رقم العرض --}}
                        <td style="padding:13px 16px;white-space:nowrap;">
                            <span style="font-family:monospace;font-size:13px;font-weight:700;color:#4f46e5;">
                                {{ $q->quotation_number }}
                            </span>
                        </td>

                        {{-- العميل --}}
                        <td style="padding:13px 16px;">
                            <span style="font-size:13px;color:#1e293b;font-weight:500;">
                                {{ $q->customer?->name ?? '—' }}
                            </span>
                        </td>

                        {{-- التاريخ --}}
                        <td style="padding:13px 16px;white-space:nowrap;">
                            <span style="font-size:13px;color:#475569;">
                                {{ $q->date instanceof \Carbon\Carbon ? $q->date->format('Y/m/d') : \Carbon\Carbon::parse($q->date)->format('Y/m/d') }}
                            </span>
                        </td>

                        {{-- صالح حتى --}}
                        <td style="padding:13px 16px;white-space:nowrap;">
                            @if($q->valid_until)
                                <span style="font-size:13px;color:{{ $q->isExpired() ? '#ef4444' : '#475569' }};">
                                    {{ $q->valid_until instanceof \Carbon\Carbon ? $q->valid_until->format('Y/m/d') : \Carbon\Carbon::parse($q->valid_until)->format('Y/m/d') }}
                                </span>
                                @if($q->isExpired())
                                    <span style="font-size:11px;color:#ef4444;font-weight:600;margin-right:4px;">(منتهي)</span>
                                @endif
                            @else
                                <span style="color:#94a3b8;">—</span>
                            @endif
                        </td>

                        {{-- الإجمالي --}}
                        <td style="padding:13px 16px;text-align:left;white-space:nowrap;">
                            <span style="font-size:13px;font-weight:600;color:#1e293b;font-family:monospace;">
                                {{ $fmt($q->total ?? 0) }}
                            </span>
                            <span style="font-size:11px;color:#94a3b8;margin-right:3px;">ر.س</span>
                        </td>

                        {{-- الحالة --}}
                        <td style="padding:13px 16px;">
                            <span style="display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['color'] }};">
                                <span style="width:6px;height:6px;border-radius:50%;background:{{ $sc['dot'] }};flex-shrink:0;"></span>
                                {{ $sc['label'] }}
                            </span>
                        </td>

                        {{-- الإجراءات --}}
                        <td style="padding:13px 16px;text-align:center;">
                            <a href="{{ route('accounting.quotations.show', $q) }}"
                               style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;background:#f5f3ff;color:#6d28d9;border:1px solid #e9d5ff;border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;transition:background .15s;"
                               onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                                عرض
                            </a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($quotations->hasPages())
        <div style="padding:16px 20px;border-top:1px solid #f1f5f9;">
            {{ $quotations->links() }}
        </div>
        @endif

    @endif

</div>

@endsection
