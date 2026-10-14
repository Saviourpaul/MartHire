<x-guest-card-layout title="Confirm password | MartHire">
    <div class="flex flex-col gap-6">
        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Confirm your password</h1>
        <p class="text-sm leading-6 text-gray-500 dark:text-gray-400">For your security, confirm your password to continue.</p>
        <form method="POST" action="{{ route('password.confirm') }}" class="flex flex-col gap-5">
            @csrf
            <div>
                <label for="password" class="mb-2 block text-sm font-medium">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required autofocus class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <button type="submit" class="min-h-[44px] rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white hover:bg-blue-700">Confirm password</button>
        </form>
    </div>
</x-guest-card-layout>
