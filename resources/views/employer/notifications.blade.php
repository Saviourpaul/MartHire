<x-layout>
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Notifications</h1>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6 xl:max-w-3xl">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Recent Notifications</h2>
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <a href="{{ route('jobs') }}" class="block py-4 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-4"><h3 class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Job updates</h3><span class="text-theme-xs text-gray-500 dark:text-gray-400">Just now</span></div>
                        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Track applications and job posting updates from one place.</p>
                    </a>
                    <a href="{{ route('employer.Applied-Candidates') }}" class="block py-4 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-4"><h3 class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Candidate activity</h3><span class="text-theme-xs text-gray-500 dark:text-gray-400">Today</span></div>
                        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Review new candidate submissions for your open roles.</p>
                    </a>
                    <a href="{{ route('profile.edit') }}" class="block py-4 transition hover:bg-gray-50 dark:hover:bg-white/[0.03]">
                        <div class="flex items-center justify-between gap-4"><h3 class="text-theme-sm font-medium text-gray-800 dark:text-white/90">Profile completion</h3><span class="text-theme-xs text-gray-500 dark:text-gray-400">Today</span></div>
                        <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Keep your employer profile details current.</p>
                    </a>
                </div>
            </section>
        </div>
    </main>
</x-layout>
