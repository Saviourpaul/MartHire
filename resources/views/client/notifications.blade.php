<x-layout>
    <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
        <div class="mb-6 flex flex-col justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-5 py-5 sm:flex-row sm:items-center dark:border-gray-800 dark:bg-white/[0.03]">
            <div>
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Notifications</h1>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Stay updated on your application progress.</p>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Notifications</h2>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($notifications as $notification)
                    <a href="{{ $notification->data['action_url'] ?? route('client.jobs-listings') }}" class="block px-6 py-4 transition hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <p class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $notification->data['title'] ?? 'Application update' }}</p>
                                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">{{ $notification->data['message'] ?? 'Your application status changed.' }}</p>
                            </div>
                            <small class="text-theme-xs whitespace-nowrap text-gray-400 dark:text-gray-500">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                    </a>
                @empty
                    <div class="px-6 py-10 text-center text-theme-sm text-gray-500 dark:text-gray-400">No notifications yet.</div>
                @endforelse
            </div>

            <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                {{ $notifications->links() }}
            </div>
        </section>
    </div>
</x-layout>
