@php
$extraQuery = $extraQuery ?? [];
$periodOptions = [
    'week'  => 'آخر 7 أيام',
    'month' => 'هذا الشهر',
];
@endphp

<div class="ac-report-presets">
    @foreach($periodOptions as $value => $label)
        <a href="{{ route($routeName, array_merge($extraQuery, ['period' => $value])) }}"
           class="ac-report-presets__pill {{ ($currentPeriod ?? null) === $value ? 'ac-report-presets__pill--active' : '' }}">
            {{ $label }}
        </a>
    @endforeach

    <span class="ac-report-presets__pill {{ ($currentPeriod ?? null) === 'custom' ? 'ac-report-presets__pill--active' : '' }}">
        فترة مخصصة
    </span>
</div>
