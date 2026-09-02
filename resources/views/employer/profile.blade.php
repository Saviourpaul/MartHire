<x-layout>
    @php($user = auth()->user())
    <main>
        <div class="mx-auto max-w-(--breakpoint-2xl) p-4 pb-20 md:p-6 md:pb-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">My Profile</h1>
                <a href="{{ route('profile.edit') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Edit Profile</a>
            </div>

            <div class="grid grid-cols-12 gap-4 md:gap-6">
                <div class="col-span-12 xl:col-span-8">
                    @include('profile.partials.completion-tracker', ['user' => $user])

                    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] sm:p-6">
                        <div class="mb-6 flex items-center gap-4">
                            <img src="{{ $user->profileImageUrl() }}" alt="{{ $user->full_name }}" class="size-20 rounded-full object-cover">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ trim($user->first_name.' '.$user->last_name) }}</h2>
                                <p class="text-theme-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>

                        <dl class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">Phone Number</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->phone ?: 'N/A' }}</dd></div>
                            <div><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">Nationality</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->nationality ?: 'N/A' }}</dd></div>
                            <div><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">State of Origin</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->state_of_origin ?: 'N/A' }}</dd></div>
                            <div><dt class="mb-1 text-theme-xs text-gray-500 dark:text-gray-400">Local Government</dt><dd class="text-theme-sm font-medium text-gray-800 dark:text-white/90">{{ $user->local_government_area ?: 'N/A' }}</dd></div>
                        </dl>
                    </section>
                </div>
            </div>
        </div>
    </main>
</x-layout>
