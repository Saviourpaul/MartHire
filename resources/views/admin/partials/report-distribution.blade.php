@php
    $total = array_sum($chart['series'] ?? []);
@endphp

@if ($total > 0)
    <div class="space-y-4">
        @foreach ($chart['labels'] as $index => $label)
            @php
                $value = (int) ($chart['series'][$index] ?? 0);
                $percent = $total > 0 ? round(($value / $total) * 100, 1) : 0;
            @endphp
            <div>
                <div class="mb-2 flex items-center justify-between gap-3">
                    <span class="rounded-full px-2.5 py-1 text-theme-xs font-medium {{ $statusClass($label) }}">{{ $label }}</span>
                    <span class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ number_format($value) }} <span class="text-gray-500 dark:text-gray-400">({{ $percent }}%)</span></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                    <div class="h-full rounded-full" style="width: {{ $percent }}%; background-color: {{ $chart['colors'][$index] ?? '#465fff' }}"></div>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="flex h-48 items-center justify-center rounded-lg bg-gray-50 text-theme-sm text-gray-500 dark:bg-gray-900 dark:text-gray-400">No records available for this period.</div>
@endif
