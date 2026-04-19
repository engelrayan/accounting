@extends('accounting._layout')

@section('title', $quotation->quotation_number)

@section('content')
@php
$statusMap = [
    'draft'    => ['bg'=>'#f1f5f9','color'=>'#475569','dot'=>'#94a3b8','label'=>'مسودة'],
    'sent'     => ['bg'=>'#eff6ff','color'=>'#1d4ed8','dot'=>'#3b82f6','label'=>'مرسل'],
    'accepted' => ['bg'=>'#f0fdf4','color'=>'#15803d','dot'=>'#22c55e','label'=>'مقبول'],
    'rejected' => ['bg'=>'#fef2f2','color'=>'#b91c1c','dot'=>'#ef4444','label'=>'مرفوض'],
    'expired'  => ['bg'=>'#fff7ed','color'=>'#c2410c','dot'=>'#f97316','label'=>'منتهي'],
    'invoiced' => ['bg'=>'#faf5ff','color'=>'#7c3aed','dot'=>'#a855f7','label'=>'محوَّل لفاتورة'],
];
$st = $statusMap[$quotation->status] ?? $statusMap['draft'];

$isExpired = $quotation->valid_until && \Carbon\Carbon::parse($quotation->valid_until)->isPast()
             && !in_array($quotation->status, ['accepted','invoiced']);
@endphp

<div style="font-family:'Cairo',sans-serif;direction:rtl;">

    {{-- Topbar --}}
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;">
        <a href="{{ route('accounting.quotations.index') }}"
           style="display:inline-flex;align-items:center;gap:.4rem;color:#4f46e5;text-decoration:none;font-size:.92rem;font-weight:600;">
            &#8594; عروض الأسعار
        </a>
        <h1 style="margin:0;font-size:1.2rem;font-weight:700;color:#1e293b;">{{ $quotation->quotation_number }}</h1>
    </div>

    {{-- Gradient Header Banner --}}
    <div style="background:linear-gradient(135deg,#1e40af 0%,#4f46e5 100%);border-radius:16px;padding:1.75rem 2rem;margin-bottom:1.5rem;color:#fff;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
            <div>
                <p style="margin:0 0 .3rem;opacity:.8;font-size:.85rem;">رقم عرض السعر</p>
                <h2 style="margin:0 0 .4rem;font-size:1.6rem;font-weight:800;letter-spacing:.5px;">{{ $quotation->quotation_number }}</h2>
                <p style="margin:0;font-size:1rem;opacity:.9;font-weight:600;">{{ $quotation->customer->name ?? '—' }}</p>
            </div>
            <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.6rem;">
                {{-- Status Badge --}}
                <span style="display:inline-flex;align-items:center;gap:.35rem;padding:.25rem .75rem;border-radius:20px;font-size:.78rem;font-weight:700;background:{{ $st['bg'] }};color:{{ $st['color'] }};">
                    <span style="width:7px;height:7px;border-radius:50%;background:{{ $st['dot'] }};display:inline-block;"></span>
                    {{ $st['label'] }}
                </span>
                {{-- Valid Until --}}
                @if($quotation->valid_until)
                    <span style="font-size:.85rem;opacity:.85;{{ $isExpired ? 'color:#fca5a5;' : '' }}">
                        صالح حتى: {{ \Carbon\Carbon::parse($quotation->valid_until)->format('Y/m/d') }}
                        @if($isExpired) (منتهي الصلاحية) @endif
                    </span>
                @else
                    <span style="font-size:.85rem;opacity:.75;">بدون تاريخ انتهاء</span>
                @endif
            </div>
        </div>
    </div>

    {{-- 2-col grid --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem;">

        {{-- Left: بيانات العرض --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
            <h3 style="margin:0 0 1.1rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                بيانات العرض
            </h3>
            <table style="width:100%;border-collapse:collapse;">
                <tbody>
                    <tr>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;width:45%;">العميل</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;font-weight:700;">{{ $quotation->customer->name ?? '—' }}</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">التاريخ</td>
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;">{{ \Carbon\Carbon::parse($quotation->date)->format('Y/m/d') }}</td>
                    </tr>
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">صالح حتى</td>
                        <td style="padding:.55rem 0;font-size:.88rem;{{ $isExpired ? 'color:#ef4444;font-weight:700;' : 'color:#1e293b;' }}">
                            {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('Y/m/d') : 'غير محدد' }}
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
                        <td style="padding:.55rem 0;font-size:.88rem;color:#1e293b;">{{ $quotation->created_at->format('Y/m/d H:i') }}</td>
                    </tr>
                    @if($quotation->status === 'invoiced' && $quotation->invoice_id)
                    <tr style="border-top:1px solid #f1f5f9;">
                        <td style="padding:.55rem 0;font-size:.88rem;color:#64748b;font-weight:600;">الفاتورة المرتبطة</td>
                        <td style="padding:.55rem 0;">
                            <a href="{{ route('accounting.invoices.show', $quotation->invoice_id) }}"
                               style="color:#7c3aed;font-weight:700;font-size:.88rem;text-decoration:none;">
                                {{ $quotation->invoice->invoice_number ?? '#'.$quotation->invoice_id }} ←
                            </a>
                        </td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>

        {{-- Right: الملاحظات والشروط --}}
        <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);padding:1.5rem;">
            <h3 style="margin:0 0 1.1rem;font-size:1rem;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:.65rem;">
                الملاحظات والشروط
            </h3>
            <div style="margin-bottom:1rem;">
                <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:700;color:#374151;">الملاحظات</p>
                @if($quotation->notes)
                    <p style="margin:0;font-size:.88rem;color:#475569;line-height:1.7;white-space:pre-wrap;">{{ $quotation->notes }}</p>
                @else
                    <p style="margin:0;font-size:.85rem;color:#94a3b8;font-style:italic;">لا توجد ملاحظات</p>
                @endif
            </div>
            @if($quotation->terms)
            <div style="border-top:1px solid #f1f5f9;padding-top:.85rem;">
                <p style="margin:0 0 .4rem;font-size:.85rem;font-weight:700;color:#374151;">الشروط والأحكام</p>
                <p style="margin:0;font-size:.85rem;color:#475569;line-height:1.7;white-space:pre-wrap;">{{ $quotation->terms }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- Items Table --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;box-shadow:0 1px 4px rgba(0,0,0,.05);margin-bottom:1.25rem;">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;">
            <h3 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">بنود عرض السعر</h3>
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
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الخصم%</th>
                        <th style="padding:.7rem 1rem;text-align:center;font-size:.8rem;font-weight:700;color:#64748b;border-bottom:1px solid #e2e8f0;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $i => $item)
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
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->discount_rate ?? 0, 2) }}%</td>
                        <td style="padding:.7rem 1rem;text-align:center;font-size:.88rem;font-weight:700;color:#1e293b;border-bottom:1px solid #f1f5f9;">{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="8" style="padding:1rem 1.5rem;">
                            <div style="display:flex;justify-content:flex-end;">
                                <div style="min-width:260px;">
                                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                                        <span>المجموع الفرعي:</span>
                                        <span>{{ number_format($quotation->subtotal, 2) }} ريال</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                                        <span>الضريبة:</span>
                                        <span>{{ number_format($quotation->tax_amount, 2) }} ريال</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:.4rem 0;font-size:.9rem;color:#475569;border-bottom:1px solid #f1f5f9;">
                                        <span>الخصم:</span>
                                        <span>{{ number_format($quotation->discount_amount, 2) }} ريال</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;padding:.55rem 0;font-size:1.08rem;font-weight:800;color:#1e293b;">
                                        <span>الإجمالي:</span>
                                        <span>{{ number_format($quotation->total, 2) }} ريال</span>
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

            @if($quotation->isDraft())
                {{-- Send --}}
                <form method="POST" action="{{ route('accounting.quotations.send', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد إرسال هذا العرض للعميل؟')"
                            style="padding:.5rem 1.25rem;background:#2563eb;color:#fff;border:1px solid #1d4ed8;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        إرسال للعميل
                    </button>
                </form>
                {{-- Edit --}}
                <a href="{{ route('accounting.quotations.edit', $quotation) }}"
                   style="padding:.5rem 1.25rem;background:#fff;color:#475569;border:1px solid #d1d5db;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;text-decoration:none;display:inline-block;">
                    تعديل
                </a>

            @elseif($quotation->isSent())
                {{-- Accept --}}
                <form method="POST" action="{{ route('accounting.quotations.accept', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit"
                            style="padding:.5rem 1.25rem;background:#15803d;color:#fff;border:1px solid #166534;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تأكيد القبول
                    </button>
                </form>
                {{-- Reject --}}
                <form method="POST" action="{{ route('accounting.quotations.reject', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد رفض هذا العرض؟')"
                            style="padding:.5rem 1.25rem;background:#fff;color:#ef4444;border:1px solid #fca5a5;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        رفض
                    </button>
                </form>
                {{-- Convert --}}
                <form method="POST" action="{{ route('accounting.quotations.convert', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد تحويل هذا العرض إلى فاتورة؟')"
                            style="padding:.5rem 1.25rem;background:#7c3aed;color:#fff;border:1px solid #6d28d9;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تحويل لفاتورة
                    </button>
                </form>

            @elseif($quotation->isAccepted())
                {{-- Convert --}}
                <form method="POST" action="{{ route('accounting.quotations.convert', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد تحويل هذا العرض إلى فاتورة؟')"
                            style="padding:.5rem 1.25rem;background:#7c3aed;color:#fff;border:1px solid #6d28d9;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        تحويل لفاتورة
                    </button>
                </form>
                {{-- Reject --}}
                <form method="POST" action="{{ route('accounting.quotations.reject', $quotation) }}" style="display:inline;">
                    @csrf
                    <button type="submit" onclick="return confirm('هل تريد رفض هذا العرض؟')"
                            style="padding:.5rem 1.25rem;background:#fff;color:#ef4444;border:1px solid #fca5a5;border-radius:8px;font-family:'Cairo',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;">
                        رفض
                    </button>
                </form>

            @elseif($quotation->isInvoiced())
                <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:.85rem 1.25rem;display:flex;align-items:center;gap:.75rem;">
                    <span style="font-size:1.2rem;">✅</span>
                    <div>
                        <p style="margin:0;font-size:.9rem;font-weight:700;color:#15803d;">تم التحويل للفاتورة</p>
                        @if($quotation->invoice_id)
                        <a href="{{ route('accounting.invoices.show', $quotation->invoice_id) }}"
                           style="font-size:.85rem;color:#15803d;font-weight:600;text-decoration:none;">
                            {{ $quotation->invoice->invoice_number ?? 'عرض الفاتورة' }} ←
                        </a>
                        @endif
                    </div>
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
