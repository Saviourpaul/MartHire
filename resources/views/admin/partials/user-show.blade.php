<div class="p-6">
    <div class="px-2 pr-14">
        <h2 class="text-lg font-medium text-gray-900">User Profile</h2>
        <p class="mt-1 text-sm text-gray-500">Complete profile and account details for this user.</p>
    </div>

    <div class="mt-6 flex items-center gap-4">
        <div class="h-16 w-16 overflow-hidden rounded-full">
            <img src="{{ $user->profileImageUrl() }}" alt="{{ $user->full_name }}" class="h-full w-full object-cover" />
        </div>
        <div>
            <p class="text-lg font-semibold text-gray-900">{{ $user->full_name }}</p>
            <p class="text-sm text-gray-500">{{ $user->email }}</p>
        </div>
    </div>

    <dl class="mt-6 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Role</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->role->label() }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Status</dt>
            <dd class="mt-1">
                <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $user->status->badgeClass() }}">{{ $user->status->label() }}</span>
            </dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Phone</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->phone_number ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Date of Birth</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->date_of_birth?->format('M j, Y') ?? 'Not provided' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Address</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->address ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Nationality</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->nationality ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">State of Origin</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->stateOfOrigin->name ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">LGA</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->localGovernment->name ?? 'Not provided' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Registered</dt>
            <dd class="mt-1 text-sm font-medium text-gray-900">{{ $user->created_at->format('M j, Y g:i A') }}</dd>
        </div>
       
    </dl>

    <div class="mt-6 flex justify-end">
        <x-secondary-button type="button" @click="show = false">
            Close
        </x-secondary-button>
    </div>
</div>
