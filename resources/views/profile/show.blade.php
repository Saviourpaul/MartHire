<x-layout>
    <main>
        <div class="mx-auto flex max-w-screen-xl flex-col gap-6 p-4 pb-20 md:p-6">
            <div><a href="{{ auth()->user()->isAdmin() ? route('applicants') : route('employer.Applied-Candidates') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400">Back to candidates</a><h1 class="mt-2 text-2xl font-semibold text-gray-800 dark:text-white/90">Applicant Profile</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">This profile is read-only.</p></div>
            <section class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-4 border-b border-gray-100 pb-5 dark:border-gray-800"><x-team-avatar :user="$user" class="h-16 w-16 text-lg"/><div><h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">{{ $user->first_name }} {{ $user->last_name }}</h2><p class="mt-1 break-all text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p></div></div>
                <dl class="mt-5 grid gap-6 sm:grid-cols-2">
                    @foreach (['phone' => 'Phone', 'nationality' => 'Nationality', 'state_of_origin' => 'State of origin', 'local_government_area' => 'Local government area', 'address' => 'Address', 'zipcode' => 'Postal code'] as $field => $label)
                        <div><dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="mt-1 break-words text-sm font-medium text-gray-800 dark:text-gray-200">{{ $user->{$field} ?: 'Not provided' }}</dd></div>
                    @endforeach
                </dl>
            </section>
        </div>
    </main>
</x-layout>
