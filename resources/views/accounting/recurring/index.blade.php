@extends('accounting._layout')
@section('title', 'القيود المتكررة')

@section('topbar-actions')
<div style="display:flex;gap:.75rem;">
    @if($dueCount > 0)
    <form method="POST" action="{{ route('accounting.recurring.run-due') }}"
          onsubmit="return confirm('توليد {{ $dueCount }} قيد مستحق الآن؟')">
        @csrf
        <button type="submit" class="ac-btn ac-btn--warning">
            ⚡ تشغيل {{ $dueCount }} مستحق
        </button>
    </form>
    @endif
    <a href="{{ route('accounting.recurring.create') }}" class="ac-btn ac-btn--primary">+ قيد متكرر جديد</a>
</div>
@endsection

@section('content')

@if($entries->isEmpty())
    <div class="ac-card ac-empty-state" style="text-align:center;padding:3rem;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:48px;height:48px;margin:0 auto 1rem;color:var(--ac-muted);">
            <polyline points="17,1 21,5 17,9"/>
            <path d="M3 11V9a4 4 0 014-4h14"/>
            <polyline points="7,23 3,19 7,15"/>
            <path d="M21 13v2a4 4 0 01-4 4H3"/>
        </svg>
        <p style="color:var(--ac-muted);margin-bottom:1.5rem;">لا توجد قيود متكررة</p>
        <a href="{{ route('accounting.recurring.create') }}" class="ac-btn ac-btn--primary">إنشاء أول قيد متكرر</a>
    </div>
@else
    <div class="ac-card">
        <table class="ac-table">
            <thead>
                <tr>
                    <th>الوصف</th>
                    <th>التكرار</th>
                    <th>التاريخ التالي</th>
                    <th>تاريخ الانتهاء</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr>
                    <td style="font-weight:600;">{{ $entry->description }}</td>
                    <td>{{ $entry->frequencyLabel() }}</td>
                    <td>
                        {{ $entry->next_run_date->format('Y/m/d') }}
                        @if($entry->isDue())
                            <span class="ac-badge ac-badge--warning" style="margin-right:.5rem;font-size:.75rem;">مستحق</span>
                        @endif
                    </td>
                    <td>{{ $entry->end_date?->format('Y/m/d') ?? 'بلا نهاية' }}</td>
                    <td>
                        <span class="ac-badge {{ $entry->is_active ? 'ac-badge--success' : 'ac-badge--muted' }}">
                            {{ $entry->is_active ? 'نشط' : 'موقوف' }}
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:.5rem;">
                            <a href="{{ route('accounting.recurring.show', $entry) }}"
                               class="ac-btn ac-btn--ghost ac-btn--sm">عرض</a>

                            <form method="POST" action="{{ route('accounting.recurring.toggle', $entry) }}">
                                @csrf
                                <button type="submit" class="ac-btn ac-btn--ghost ac-btn--sm">
                                    {{ $entry->is_active ? 'إيقاف' : 'تفعيل' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div style="margin-top:1rem;">
            {{ $entries->links() }}
        </div>
    </div>
@endif

@endsection
