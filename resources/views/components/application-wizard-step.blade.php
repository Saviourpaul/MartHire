@props([
    'title',
    'index',
    'description' => null,
])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-gray-200 bg-white shadow-theme-xs dark:border-gray-800 dark:bg-white/[0.03]']) }}
    data-wizard-step="{{ $index }}" @if ($index > 0) hidden @endif>
    <header class="border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
        <p class="text-theme-xs font-medium uppercase text-brand-600 dark:text-brand-400">Step {{ $index + 1 }}</p>
        <h2 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white/90">{{ $title }}</h2>
        @if ($description)
            <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
        @endif
    </header>

    <div class="p-5 sm:p-6">
        {{ $slot }}
    </div>
</section>
