<x-layout>
    <main>
        <div class="mx-auto flex max-w-screen-2xl flex-col gap-6 p-4 pb-20 md:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div><h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Notifications</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Updates about your account and hiring team.</p></div>
                <form method="POST" action="{{ route('employer.notifications.read-all') }}">@csrf<button type="submit" class="min-h-[44px] rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">Mark all as read</button></form>
            </div>
            @if (session('success'))<div role="status" class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-500/10 dark:text-green-300">{{ session('success') }}</div>@endif
            <section aria-label="Recent notifications" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($notifications as $notification)
                        <li class="flex flex-col gap-4 px-5 py-5 sm:flex-row sm:items-start sm:justify-between sm:px-6">
                            <div class="flex min-w-0 gap-3"><span @class(['mt-1.5 h-2 w-2 shrink-0 rounded-full', 'bg-blue-600' => ! $notification->read_at, 'bg-gray-300 dark:bg-gray-700' => $notification->read_at]) aria-label="{{ $notification->read_at ? 'Read' : 'Unread' }}"></span><div class="min-w-0"><h2 class="text-sm font-semibold text-gray-800 dark:text-white/90">{{ $notification->data['title'] ?? 'Team update' }}</h2><p class="mt-1 break-words text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $notification->data['message'] ?? '' }}</p><time datetime="{{ $notification->created_at->toIso8601String() }}" class="mt-2 block text-xs text-gray-500 dark:text-gray-400">{{ $notification->created_at->diffForHumans() }}</time>@if (! empty($notification->data['action_url']))<a href="{{ $notification->data['action_url'] }}" class="mt-3 inline-flex min-h-[44px] items-center text-sm font-medium text-blue-600 hover:underline dark:text-blue-400">View details</a>@endif</div></div>
                            @unless ($notification->read_at)<form method="POST" action="{{ route('employer.notifications.read', $notification->id) }}" class="shrink-0">@csrf @method('PATCH')<button type="submit" class="min-h-[44px] rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Mark as read</button></form>@endunless
                        </li>
                    @empty
                        <li class="flex flex-col items-center gap-2 px-6 py-16 text-center"><span class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-blue-500/15 dark:text-blue-300" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" stroke-linecap="round" stroke-linejoin="round"/></svg></span><p class="mt-2 text-sm font-medium text-gray-800 dark:text-white/90">You're all caught up</p><p class="text-sm text-gray-500 dark:text-gray-400">Team and account updates will appear here.</p></li>
                    @endforelse
                </ul>
                @if ($notifications->hasPages())<div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $notifications->links() }}</div>@endif
            </section>
        </div>
    </main>
</x-layout>
