<section aria-labelledby="job-team-heading" class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
    <h3 id="job-team-heading" class="text-base font-semibold text-gray-800 dark:text-white/90">Hiring team</h3>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Members assigned to this job.</p>
    <ul class="mt-5 flex flex-col gap-4">
        @forelse ($availableTeamMembers->whereIn('user_id', $assignedUserIds) as $member)
            <li class="flex items-center gap-3"><x-team-avatar :user="$member->user"/><div><p class="text-sm font-medium text-gray-800 dark:text-white/90">{{ trim(($member->user?->first_name ?? '').' '.($member->user?->last_name ?? '')) ?: 'Former member' }}</p><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $member->role->label() }}</p></div></li>
        @empty
            <li class="text-sm leading-6 text-gray-500 dark:text-gray-400">No members assigned yet. Organization owners and HR managers can access this job.</li>
        @endforelse
    </ul>
    @can('manageTeam', $job)
        <form action="{{ route('jobs.team.update', $job) }}" method="POST" class="mt-5 flex flex-col gap-4 border-t border-gray-100 pt-5 dark:border-gray-800">
            @csrf @method('PUT')
            <fieldset class="flex flex-col gap-3"><legend class="mb-3 text-sm font-medium text-gray-800 dark:text-gray-200">Assign members</legend>
                @forelse ($availableTeamMembers as $member)
                    <label class="flex min-h-[44px] items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm dark:border-gray-700"><input type="checkbox" name="user_ids[]" value="{{ $member->user_id }}" @checked(in_array($member->user_id, old('user_ids', $assignedUserIds))) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"><span><span class="block font-medium text-gray-800 dark:text-white/90">{{ $member->user?->first_name }} {{ $member->user?->last_name }}</span><span class="text-xs text-gray-500 dark:text-gray-400">{{ $member->role->label() }}</span></span></label>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">Invite active team members before assigning them.</p>
                @endforelse
            </fieldset>
            @error('user_ids')<p role="alert" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            @error('user_ids.*')<p role="alert" class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
            <button type="submit" class="min-h-[44px] rounded-lg bg-blue-600 px-4 text-sm font-medium text-white hover:bg-blue-700">Save hiring team</button>
        </form>
    @endcan
</section>
