@props(['steps'])

<ol class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4" data-application-wizard-progress aria-label="Application progress">
    @foreach ($steps as $index => $step)
        <li class="rounded-xl border border-gray-200 bg-white p-4 shadow-theme-xs transition dark:border-gray-800 dark:bg-white/[0.03] {{ $index === 0 ? 'is-active border-brand-300 ring-2 ring-brand-500/10' : '' }}" data-application-wizard-progress-item>
            <div class="flex items-center gap-3">
                <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-theme-sm font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-300" data-step-number>{{ $index + 1 }}</span>
                <span class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">{{ $step }}</span>
            </div>
        </li>
    @endforeach
</ol>
