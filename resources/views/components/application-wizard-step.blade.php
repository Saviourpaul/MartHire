@props(['title', 'index'])

<section {{ $attributes->merge(['class' => 'space-y-6']) }} data-application-wizard-step data-step-index="{{ $index }}" aria-labelledby="application-step-{{ $index }}-title" @if($index !== 0) hidden @endif>
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
        <div class="mb-6 flex items-start justify-between gap-4 border-b border-gray-100 pb-5 dark:border-gray-800">
            <div>
                <p class="text-theme-xs font-medium uppercase text-brand-500">Step {{ $index + 1 }}</p>
                <h2 id="application-step-{{ $index }}-title" class="mt-1 text-lg font-semibold text-gray-800 dark:text-white/90">{{ $title }}</h2>
            </div>
        </div>
        {{ $slot }}
    </div>
</section>
