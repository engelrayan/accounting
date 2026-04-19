@extends('accounting._layout')

@section('title', 'أوامر الشراء')

@section('content')
<div style="font-family:'Cairo',sans-serif;direction:rtl;">

    {{-- Topbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <h1 style="margin:0;font-size:1.25rem;font-weight:800;color:#1e293b;">أوامر الشراء</h1>
        <a href="{{ route('accounting.purchase-orders.create') }}"
           style="display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.25rem;background:#065f46;color:#fff;border-radius:9px;text-decoration:none;font-size:.9rem;font-weight:700;">
            + أمر شراء جديد
        </a>
    </div>

    {{-- Gradient Header --}}
    <div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <h2 style="margin:0 0 .3rem;font-size:1.35rem;font-weight:800;">إدارة أوامر الشراء</h2>
        <p style="margin:0;opacity:.85;font-size:.9rem;">تتبع أوامر الشراء من الموردين وحالة الاستلام</p>
    </div>

    {{-- Stats Cards --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">
        {{-- مسودة --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                <p style="margin:0;font-size:.82rem;color:#64748b;font-weight:600;">مسودة</p>
                <span style="width:36px;height:36px;background:#f1f5f9;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">📋</span>
            </div>
            <p style="margin:0;font-size:1.7rem;font-weight:800;color:#475569;">{{ $draftCount }}</p>
        </div>
        {{-- مرسل للمورد --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                <p style="margin:0;font-size:.82rem;color:#64748b;font-weight:600;">مرسل للمورد</p>
                <span style="width:36px;height:36px;background:#eff6ff;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">📤</span>
            </div>
            <p style="margin:0;font-size:1.7rem;font-weight:800;color:#2563eb;">{{ $sentCount }}</p>
        </div>
        {{-- تم الاستلام --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                <p style="margin:0;font-size:.82rem;color:#64748b;font-weight:600;">تم الاستلام</p>
                <span style="width:36px;height:36px;background:#f0fdf4;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">✅</span>
            </div>
            <p style="margin:0;font-size:1.7rem;font-weight:800;color:#15803d;">{{ $receivedCount }}</p>
        </div>
        {{-- إجمالي القيمة --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.25rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.5rem;">
                <p style="margin:0;font-size:.82rem;color:#64748b;font-weight:600;">إجمالي القيمة</p>
                <span style="width:36px;height:36px;background:#fef3c7;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;">💰</span>
            </div>
            <p style="margin:0;font-size:1.35rem;font-weight:800;color:#d97706;">{{ number_format($totalValue, 2) }} <span style="font-size:.78rem;font-weight:600;color:#92400e;">ريال</span></p>
        </div>
    </div>

    {{-- Filters --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.1rem 1.5rem;margin-bottom:1.25rem;">
        <form method="GET" action="{{ route('accounting.purchase-orders.index') }}"
              style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;">
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.35rem;">الحالة</label>
                <select name="status"
                        style="padding:.5rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;color:#1e293b;background:#fff;">
                    <option value="">كل الحالات</option>
                    <option value="draft"     {{ request('status')==='draft'     ?'selected':'' }}>مسودة</option>
                    <option value="sent"      {{ request('status')==='sent'      ?'selected':'' }}>مرسل للمورد</option>
                    <option value="received"  {{ request('status')==='received'  ?'selected':'' }}>تم الاستلام</option>
                    <option value="invoiced"  {{ request('status')==='invoiced'  ?'selected':'' }}>محوَّل لفاتورة</option>
                    <option value="cancelled" {{ request('status')==='cancelled' ?'selected':'' }}>ملغى</option>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:.82rem;font-weight:600;color:#374151;margin-bottom:.35rem;">المورد</label>
                <select name="vendor_id"
                        style="padding:.5rem .85rem;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;color:#1e293b;background:#fff;min-width:180px;">
                    <option value="">كل الموردين</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ request('vendor_id')==$vendor->id?'selected':'' }}>
                            {{ $vendor->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div style="display:flex;gap:.6rem;">
                <button type="submit"
                        style="padding:.5rem 1.1rem;background:#065f46;color:#fff;border:none;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                    تصفية
                </button>
                <a href="{{ route('accounting.purchase-orders.index') }}"
                   style="padding:.5rem 1rem;background:#fff;color:#64748b;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:600;text-decoration:none;display:inline-block;">
                    إعادة تعيين
                </a>
            </div>
        </form>
    </div>

    {{-- Table Card --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">قائمة أوامر الشراء</h3>
        </div>

        @if($purchaseOrders->isEmpty())
        <div style="padding:3rem;text-align:center;color:#94a3b8;">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">📦</p>
            <p style="margin:0;font-size:.95rem;font-weight:600;">لا توجد أوامر شراء</p>
            <a href="{{ route('accounting.purchase-orders.create') }}"
               style="display:inline-block;margin-top:1rem;padding:.55rem 1.25rem;background:#065f46;color:#fff;border-radius:9px;text-decoration:none;font-size:.88rem;font-weight:700;">
                + إنشاء أمر شراء جديد
            </a>
        </div>
        @else
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.7rem 1.1rem;text-align:right;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">رقم الأمر</th>
                        <th style="padding:.7rem 1.1rem;text-align:right;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">المورد</th>
                        <th style="padding:.7rem 1.1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">التاريخ</th>
                        <th style="padding:.7rem 1.1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">تاريخ الاستلام المتوقع</th>
                        <th style="padding:.7rem 1.1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">الإجمالي</th>
                        <th style="padding:.7rem 1.1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">الحالة</th>
                        <th style="padding:.7rem 1.1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;white-space:nowrap;">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrders as $i => $po)
                    @php
                    $poStatuses = [
                        'draft'     => ['bg'=>'#f1f5f9','color'=>'#475569','dot'=>'#94a3b8','label'=>'مسودة'],
                        'sent'      => ['bg'=>'#eff6ff','color'=>'#1d4ed8','dot'=>'#3b82f6','label'=>'مرسل للمورد'],
                        'received'  => ['bg'=>'#f0fdf4','color'=>'#15803d','dot'=>'#22c55e','label'=>'تم الاستلام'],
                        'cancelled' => ['bg'=>'#fef2f2','color'=>'#b91c1c','dot'=>'#ef4444','label'=>'ملغى'],
                        'invoiced'  => ['bg'=>'#faf5ff','color'=>'#7c3aed','dot'=>'#a855f7','label'=>'محوَّل لفاتورة'],
                    ];
                    $pst = $poStatuses[$po->status] ?? $poStatuses['draft'];
                    @endphp
                    <tr style="{{ $i % 2 === 0 ? 'background:#fff;' : 'background:#fafafa;' }}">
                        <td style="padding:.75rem 1.1rem;font-size:.88rem;font-weight:700;color:#1e293b;border-bottom:1px solid #f1f5f9;">
                            {{ $po->po_number }}
                        </td>
                        <td style="padding:.75rem 1.1rem;font-size:.88rem;color:#374151;border-bottom:1px solid #f1f5f9;">
                            {{ $po->vendor->name ?? '—' }}
                        </td>
                        <td style="padding:.75rem 1.1rem;text-align:center;font-size:.85rem;color:#64748b;border-bottom:1px solid #f1f5f9;">
                            {{ \Carbon\Carbon::parse($po->date)->format('Y/m/d') }}
                        </td>
                        <td style="padding:.75rem 1.1rem;text-align:center;font-size:.85rem;color:#64748b;border-bottom:1px solid #f1f5f9;">
                            {{ $po->expected_date ? \Carbon\Carbon::parse($po->expected_date)->format('Y/m/d') : '—' }}
                        </td>
                        <td style="padding:.75rem 1.1rem;text-align:center;font-size:.88rem;font-weight:700;color:#1e293b;border-bottom:1px solid #f1f5f9;">
                            {{ number_format($po->total, 2) }} ريال
                        </td>
                        <td style="padding:.75rem 1.1rem;text-align:center;border-bottom:1px solid #f1f5f9;">
                            <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;background:{{ $pst['bg'] }};color:{{ $pst['color'] }};">
                                <span style="width:7px;height:7px;border-radius:50%;background:{{ $pst['dot'] }};display:inline-block;"></span>
                                {{ $pst['label'] }}
                            </span>
                        </td>
                        <td style="padding:.75rem 1.1rem;text-align:center;border-bottom:1px solid #f1f5f9;">
                            <a href="{{ route('accounting.purchase-orders.show', $po) }}"
                               style="padding:.35rem .85rem;background:#fff;color:#065f46;border:1px solid #a7f3d0;border-radius:7px;font-family:'Cairo',sans-serif;font-size:.8rem;font-weight:700;text-decoration:none;display:inline-block;">
                                عرض
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($purchaseOrders->hasPages())
        <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;display:flex;justify-content:center;">
            {{ $purchaseOrders->withQueryString()->links() }}
        </div>
        @endif
        @endif
    </div>

</div>
@endsection
