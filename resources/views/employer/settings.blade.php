<x-layout>
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">Settings</h1>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6 xl:max-w-3xl">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Account Settings</h2>
                <p class="mt-1 text-theme-sm text-gray-500 dark:text-gray-400">Manage your profile and security settings from the profile page.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <a href="{{ route('profile.edit') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Edit Profile</a>
                    <a href="{{ route('jobs') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-theme-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">Manage Jobs</a>
                </div>
            </section>
        </div>
    </main>
</x-layout>
