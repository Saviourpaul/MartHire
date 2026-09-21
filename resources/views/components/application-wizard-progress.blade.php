@props(['steps'])

<section class="rounded-lg border border-gray-200 bg-white p-4 shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03] sm:p-5" aria-label="Application progress">
    <div class="flex items-center justify-between gap-4">
        <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-200" data-progress-status>
            Step 1 of {{ count($steps) }}
        </p>
        <p class="text-theme-sm font-medium text-brand-600 dark:text-brand-400" data-overall-percent>0%</p>
    </div>

    <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/[0.08]" role="progressbar"
        aria-label="Application completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"
        data-overall-progress>
        <div class="h-full w-0 rounded-full bg-brand-500 transition-[width] duration-200" data-overall-bar></div>
    </div>

    <ol class="mt-5 grid grid-cols-4 gap-2 sm:gap-4">
        @foreach ($steps as $index => $step)
            <li class="min-w-0" data-progress-step>
                <div class="flex items-center gap-2">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full border border-gray-300 bg-white text-theme-xs font-semibold text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                        data-progress-marker aria-hidden="true">
                        {{ $index + 1 }}
                    </span>
                    <span class="hidden min-w-0 text-theme-xs font-medium text-gray-500 dark:text-gray-400 lg:block"
                        data-progress-label>{{ $step }}</span>
                </div>
            </li>
        @endforeach
    </ol>
</section>
