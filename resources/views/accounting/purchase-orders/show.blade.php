@extends('accounting._layout')

@section('title', $purchaseOrder->po_number)

@section('content')
@php
$statusMap = [
    'draft'     => ['bg'=>'#f1f5f9','color'=>'#475569','dot'=>'#94a3b8','label'=>'مسودة'],
    'sent'      => ['bg'=>'#eff6ff','color'=>'#1d4ed8','dot'=>'#3b82f6','label'=>'مرسل للمورد'],
    'received'  => ['bg'=>'#f0fdf4','color'=>'#15803d','dot'=>'#22c55e','label'=>'تم الاستلام'],
    'invoiced'  => ['bg'=>'#faf5ff','color'=>'#7c3aed','dot'=>'#a855f7','label'=>'محوَّل لفاتورة'],
    'cancelled' => ['bg'=>'#fef2f2','color'=>'#b91c1c','dot'=>'#ef4444','label'=>'ملغى'],
];
$st = $statusMap[$purchaseOrder->status] ?? $statusMap['draft'];
@endphp

<div style="font-family:'Cairo',sans-serif;direction:rtl;">

    {{-- Topbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <a href="{{ route('accounting.purchase-orders.index') }}"
           style="display:inline-flex;align-items:center;gap:.4rem;color:#059669;text-decoration:none;font-size:.92rem;font-weight:600;">
            &#8594; أوامر الشراء
        </a>
        <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:#1e293b;">{{ $purchaseOrder->po_number }}</h1>
    </div>

    {{-- Gradient Header Banner (Green) --}}
    <div style="background:linear-gradient(135deg,#065f46 0%,#059669 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <p style="margin:0 0 .3rem;opacity:.8;font-size:.85rem;">رقم أمر الشراء</p>
                <h2 style="margin:0 0 .4rem;font-size:1.6rem;font-weight:800;letter-spacing:.5px;">{{ $purchaseOrder->po_number }}</h2>
                <p style="margin:0;font-size:1rem;opacity:.9;font-weight:600;">{{ $purchaseOrder->vendor->name ?? '—' }}</p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.6rem;">
                {{-- Status Badge --}}
                <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;background:{{ $st['bg'] }};color:{{ $st['color'] }};">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $st['dot'] }};display:inline-block;"></span>
                    {{ $st['label'] }}
                </span>
                {{-- Expected Date --}}
                @if($purchaseOrder->expected_date)
                    <span style="font-size:.85rem;opacity:.85;">
                        تاريخ الاستلام المتوقع: {{ \Carbon\Carbon::parse($purchaseOrder->expected_date)->format('Y/m/d') }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    {{-- 2-col grid --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">

        {{-- Left: بيانات الأمر --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
            <h3 style="margin:0 0 1.1rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                بيانات الأمر
            </h3>
            <table style="width:100%;border-collapse:collapse;">
                <tbody>
                    <tr>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;width:45%;">المورد</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;font-weight:700;">{{ $purchaseOrder->vendor->name ?? '—' }}</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">التاريخ</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;">{{ \Carbon\Carbon::parse($purchaseOrder->date)->format('Y/m/d') }}</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">تاريخ الاستلام المتوقع</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;">
                            {{ $purchaseOrder->expected_date ? \Carbon\Carbon::parse($purchaseOrder->expected_date)->format('Y/m/d') : '—' }}
                        </td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">الحالة</td>
                        <td style="padding:.55rem 0;">
                            <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;background:{{ $st['bg'] }};color:{{ $st['color'] }};">
                                <span style="width:7px;height:7px;border-radius:50%;background:{{ $st['dot'] }};display:inline-block;"></span>
                                {{ $st['label'] }}
                            </span>
                        </td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">تاريخ الإنشاء</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;">{{ $purchaseOrder->created_at->format('Y/m/d H:i') }}</td>
                    </tr>
                    @if($purchaseOrder->status === 'invoiced' && $purchaseOrder->purchaseInvoice)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">فاتورة الشراء</td>
                        <td style="padding:.55rem 0;">
                            <a href="{{ route('accounting.purchase-invoices.show', $purchaseOrder->purchaseInvoice) }}"
                               style="color:#7c3aed;font-weight:700;font-size:.88rem;text-decoration:none;">
                                {{ $purchaseOrder->purchaseInvoice->invoice_number ?? '#'.$purchaseOrder->purchaseInvoice->id }} ←
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Right: الملاحظات --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
            <h3 style="margin:0 0 1.1rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                ملاحظات وشروط الدفع
            </h3>
            <div style="margin-bottom:1rem;">
                <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:700;color:#374151;">الملاحظات</p>
                @if($purchaseOrder->notes)
                    <p style="margin:0;font-size:.88rem;color:#475569;line-height:1.7;white-space:pre-wrap;">{{ $purchaseOrder->notes }}</p>
                @else
                    <p style="margin:0;font-size:.85rem;color:#94a3b8;font-style:italic;">لا توجد ملاحظات</p>
                @endif
            </div>
            @if($purchaseOrder->payment_terms)
            <div style="border-top:1px solid #f1f5f9;padding-top:.85rem;">
                <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:700;color:#374151;">شروط الدفع</p>
                @php
                $payTermsMap = ['cash'=>'نقدي','net15'=>'صافي 15 يوم','net30'=>'صافي 30 يوم','net60'=>'صافي 60 يوم'];
                @endphp
                <p style="margin:0;font-size:.88rem;color:#475569;">{{ $payTermsMap[$purchaseOrder->payment_terms] ?? $purchaseOrder->payment_terms }}</p>
            </div>
            @endif
            @if($purchaseOrder->reference)
            <div style="border-top:1px solid #f1f5f9;padding-top:.85rem;margin-top:.85rem;">
                <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:700;color:#374151;">المرجع الخارجي</p>
                <p style="margin:0;font-size:.88rem;color:#475569;">{{ $purchaseOrder->reference }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:1.25rem;">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">بنود أمر الشراء</h3>
        </div>
        <div style="overflow-x:auto;">
            <table style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;width:40px;">#</th>
                        <th style="padding:.7rem 1rem;text-align:right;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الوصف</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الكمية</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الوحدة</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">السعر</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الضريبة%</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $i => $item)
                    <tr style="{{ $i % 2 === 0 ? 'background:#fff;' : 'background:#fafafa;' }}">
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.85rem;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ $i + 1 }}</td>
                        <td style="padding:.7rem 1rem;font-size:.88rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">
                            {{ $item->description }}
                            @if($item->product)
                                <br><span style="font-size:.75rem;color:#94a3b8;">{{ $item->product->name }}</span>
                            @endif
                        </td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->quantity, 2) }}</td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;color:#64748b;border-bottom:1px solid #f1f5f9;">{{ $item->unit ?? '—' }}</td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->unit_price, 2) }}</td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->tax_rate, 2) }}%</td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;font-weight:700;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="padding:1rem 1.5rem;">
                            <div style="display:flex;justify-content:flex-end;">
                                <div style="min-width:260px;">
                                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                                        <span>المجموع الفرعي:</span>
                                        <span>{{ number_format($purchaseOrder->subtotal, 2) }} ريال</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                                        <span>الضريبة:</span>
                                        <span>{{ number_format($purchaseOrder->tax_amount, 2) }} ريال</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:.55rem 0;font-size:1.08rem;font-weight:800;color:#1e293b;">
                                        <span>الإجمالي:</span>
                                        <span>{{ number_format($purchaseOrder->total, 2) }} ريال</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Actions Card --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
        <h3 style="margin:0 0 1.1rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
            الإجراءات
        </h3>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.75rem;">

            @if($purchaseOrder->status === 'draft')
                {{-- Send to Vendor --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.send', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد إرسال هذا الأمر للمورد؟')"
                            style="padding:.5rem 1.25rem;background:#2563eb;color:#fff;border:1px solid #1d4ed8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        إرسال للمورد
                    </button>
                </form>
                {{-- Edit --}}
                <a href="{{ route('accounting.purchase-orders.edit', $purchaseOrder) }}"
                   style="padding:.5rem 1.25rem;background:#fff;color:#475569;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;text-decoration:none;display:inline-block;">
                    تعديل
                </a>
                {{-- Cancel --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.cancel', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد إلغاء هذا الأمر؟ لا يمكن التراجع عن هذا الإجراء.')"
                            style="padding:.5rem 1.25rem;background:#fff;color:#ef4444;border:1px solid #fca5a5;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        إلغاء الأمر
                    </button>
                </form>

            @elseif($purchaseOrder->status === 'sent')
                {{-- Receive --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.receive', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit"
                            style="padding:.5rem 1.25rem;background:#15803d;color:#fff;border:1px solid #166534;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تأكيد الاستلام
                    </button>
                </form>
                {{-- Convert to Purchase Invoice --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.convert', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد تحويل هذا الأمر إلى فاتورة شراء؟')"
                            style="padding:.5rem 1.25rem;background:#7c3aed;color:#fff;border:1px solid #6d28d9;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تحويل لفاتورة شراء
                    </button>
                </form>
                {{-- Cancel --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.cancel', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد إلغاء هذا الأمر؟')"
                            style="padding:.5rem 1.25rem;background:#fff;color:#ef4444;border:1px solid #fca5a5;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        إلغاء الأمر
                    </button>
                </form>

            @elseif($purchaseOrder->status === 'received')
                {{-- Convert to Purchase Invoice --}}
                <form method="POST" action="{{ route('accounting.purchase-orders.convert', $purchaseOrder) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد تحويل هذا الأمر إلى فاتورة شراء؟')"
                            style="padding:.5rem 1.25rem;background:#7c3aed;color:#fff;border:1px solid #6d28d9;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تحويل لفاتورة شراء
                    </button>
                </form>

            @elseif($purchaseOrder->status === 'invoiced')
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.85rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
                    <span style="font-size:1.2rem;">✅</span>
                    <div>
                        <p style="margin:0;font-size:.9rem;font-weight:700;color:#15803d;">تم التحويل لفاتورة الشراء</p>
                        @if($purchaseOrder->purchaseInvoice)
                        <a href="{{ route('accounting.purchase-invoices.show', $purchaseOrder->purchaseInvoice) }}"
                           style="font-size:.85rem;color:#15803d;font-weight:600;text-decoration:none;">
                            {{ $purchaseOrder->purchaseInvoice->invoice_number ?? 'عرض الفاتورة' }} ←
                        </a>
                        @endif
                    </div>
                </div>

            @elseif($purchaseOrder->status === 'cancelled')
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:.85rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
                    <span style="font-size:1.2rem;">❌</span>
                    <p style="margin:0;font-size:.9rem;font-weight:700;color:#b91c1c;">تم إلغاء هذا الأمر — لا توجد إجراءات متاحة</p>
                </div>

            @else
                <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.85rem 1.25rem;color:#64748b;font-size:.88rem;">
                    لا توجد إجراءات متاحة لهذه الحالة
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
