<x-invitation-layout>
    <div class="flex flex-col gap-6">
        <div>
            <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">Hiring team invitation</span>
            <h1 class="mt-4 text-2xl font-semibold text-gray-900 dark:text-white">Join {{ $organization->name }}</h1>
            <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">{{ trim(($invitation->invitedBy?->first_name ?? '').' '.($invitation->invitedBy?->last_name ?? '')) ?: 'Your team' }} invited you to collaborate as {{ $invitation->role->label() }}.</p>
        </div>
        <dl class="flex flex-col gap-3 rounded-xl bg-gray-50 p-4 text-sm dark:bg-gray-800">
            <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400">Invited email</dt><dd class="break-all font-medium">{{ $invitation->email }}</dd></div>
            <div class="flex flex-wrap justify-between gap-2"><dt class="text-gray-500 dark:text-gray-400">Expires</dt><dd>{{ $invitation->expires_at->format('M j, Y, g:i A') }}</dd></div>
        </dl>
        @if ($errors->any())
            <div role="alert" class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-300">
                <ul class="list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @if ($canAcceptExisting)
            <form action="{{ route('team-invitations.accept', $token) }}" method="POST">
                @csrf
                <button type="submit" class="min-h-[44px] w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Accept invitation</button>
            </form>
        @elseif (auth()->check())
            <div class="flex flex-col gap-3">
                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">Sign in with {{ $invitation->email }} to accept this invitation. Your current account cannot accept it.</p>
                <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit" class="min-h-[44px] w-full rounded-lg border border-gray-300 px-4 py-3 text-sm font-medium dark:border-gray-700">Sign out</button></form>
            </div>
        @elseif ($existingAccount)
            <div class="flex flex-col gap-3">
                <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">Sign in to your existing MartHire account to accept this invitation.</p>
                <a href="{{ route('login') }}" class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700">Sign in to accept</a>
            </div>
        @else
            <form action="{{ route('team-invitations.accept', $token) }}" method="POST" class="flex flex-col gap-5">
                @csrf
                <p class="text-sm text-gray-500 dark:text-gray-400">Create your employer account to join this team.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="first_name" class="mb-2 block text-sm font-medium">First name</label><input id="first_name" name="first_name" value="{{ old('first_name') }}" required maxlength="255" autocomplete="given-name" class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700"></div>
                    <div><label for="last_name" class="mb-2 block text-sm font-medium">Last name</label><input id="last_name" name="last_name" value="{{ old('last_name') }}" required maxlength="255" autocomplete="family-name" class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700"></div>
                </div>
                <div><label for="password" class="mb-2 block text-sm font-medium">Password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password" class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700"></div>
                <div><label for="password_confirmation" class="mb-2 block text-sm font-medium">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password" class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700"></div>
                <button type="submit" class="min-h-[44px] w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">Create account and join team</button>
            </form>
        @endif
        <form action="{{ route('team-invitations.decline', $token) }}" method="POST" class="border-t border-gray-100 pt-5 dark:border-gray-800">
            @csrf
            <button type="submit" class="min-h-[44px] w-full rounded-lg px-4 text-sm font-medium text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-800">Decline invitation</button>
        </form>
    </div>
</x-invitation-layout>
