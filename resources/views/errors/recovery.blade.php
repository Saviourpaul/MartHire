<x-guest-card-layout :title="$title.' | MartHire'">
    <div role="alert" aria-labelledby="recovery-title" class="flex flex-col gap-6 text-center">
        <div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $status }}</p>
            <h1 id="recovery-title" class="mt-2 text-2xl font-semibold text-gray-900 dark:text-white">{{ $title }}</h1>
        </div>
        <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $message }}</p>
        @if ($help)
            <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $help }}</p>
        @endif
        <a href="{{ $actionUrl }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700">{{ $actionLabel }}</a>
        @if ($actionUrl !== route('home'))
            <a href="{{ route('home') }}" class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">{{ __('expiration.home_action') }}</a>
        @endif
    </div>
</x-guest-card-layout>
