<x-layout>
    @php
        $roleDescriptions = [
            'owner' => 'Manage the organization, hiring team, all jobs and hiring decisions.',
            'hr_manager' => 'Manage the hiring team, all jobs, job assignments and hiring decisions.',
            'recruiter' => 'Create jobs, edit assigned jobs and move assigned candidates through the pipeline. Final selection requires a hiring manager or HR.',
            'hiring_manager' => 'View assigned jobs, review candidate documents, move candidates and make final selection decisions.',
            'interviewer' => 'View assigned candidates who have reached interview. Documents and pipeline changes are restricted.',
        ];
    @endphp
    <main x-data="{}">
        <div class="mx-auto flex max-w-screen-2xl flex-col gap-6 p-4 pb-20 md:p-6">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div><h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Hiring Team</h1><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Manage access to {{ $organization->name }}.</p></div>
                <button type="button" @click="$refs.inviteDialog.showModal()" class="inline-flex min-h-[44px] items-center justify-center gap-2 rounded-lg bg-blue-600 px-5 py-3 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"><span aria-hidden="true">+</span> Invite member</button>
            </div>
            @if (session('success'))
                <div role="status" class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-500/10 dark:text-green-300">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-500/10 dark:text-red-300"><p class="font-medium">Please check these details.</p><ul class="mt-2 list-inside list-disc space-y-1">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif
            <section aria-labelledby="members-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-gray-800"><h2 id="members-heading" class="text-lg font-semibold text-gray-800 dark:text-white/90">Team members</h2><span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $members->total() }} members</span></div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-left text-sm">
                        <thead class="border-b border-gray-100 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400"><tr><th scope="col" class="px-6 py-4 font-medium">Member</th><th scope="col" class="px-6 py-4 font-medium">Role</th><th scope="col" class="px-6 py-4 font-medium">Status</th><th scope="col" class="px-6 py-4 font-medium">Assigned jobs</th><th scope="col" class="px-6 py-4 font-medium">Actions</th></tr></thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($members as $member)
                                @php($canManageMember = auth()->user()->can('update', $member))
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-4"><div class="flex items-center gap-3"><x-team-avatar :user="$member->user"/><div><p class="font-medium text-gray-800 dark:text-white/90">{{ trim(($member->user?->first_name ?? '').' '.($member->user?->last_name ?? '')) ?: 'Former member' }} @if ($member->user_id === auth()->id())<span class="text-xs font-normal text-gray-500">(you)</span>@endif</p><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $member->user?->email }}</p></div></div></td>
                                    <td class="px-6 py-4"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-500/15 dark:text-blue-300">{{ $member->role->label() }}</span></td>
                                    <td class="px-6 py-4"><span @class(['rounded-full px-2.5 py-1 text-xs font-medium', 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-300' => $member->status === \App\Enums\OrganizationMemberStatus::Active, 'bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' => $member->status !== \App\Enums\OrganizationMemberStatus::Active])>{{ $member->status->label() }}</span></td>
                                    <td class="px-6 py-4">{{ $member->jobs_count }}</td>
                                    <td class="px-6 py-4">@if ($canManageMember)<div class="flex gap-2"><button type="button" @click="document.getElementById('edit-member-{{ $member->id }}').showModal()" aria-label="Edit access for {{ $member->user?->email }}" class="min-h-[44px] rounded-lg border border-gray-300 px-3 text-xs font-medium hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800">Edit access</button><button type="button" @click="document.getElementById('remove-member-{{ $member->id }}').showModal()" aria-label="Remove {{ $member->user?->email }}" class="min-h-[44px] rounded-lg px-3 text-xs font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-500/10">Remove</button></div>@else<span class="text-xs text-gray-500 dark:text-gray-400">Owner managed</span>@endif</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">No team members to display.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $members->links() }}</div>
            </section>
            <section aria-labelledby="invitations-heading" class="overflow-hidden rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="border-b border-gray-100 px-5 py-5 sm:px-6 dark:border-gray-800"><h2 id="invitations-heading" class="text-lg font-semibold text-gray-800 dark:text-white/90">Invitations</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Invitations grant access only after the recipient accepts.</p></div>
                <div class="overflow-x-auto"><table class="w-full min-w-[640px] text-left text-sm"><thead class="border-b border-gray-100 text-xs text-gray-500 dark:border-gray-800 dark:text-gray-400"><tr><th scope="col" class="px-6 py-4 font-medium">Email</th><th scope="col" class="px-6 py-4 font-medium">Role</th><th scope="col" class="px-6 py-4 font-medium">Status</th><th scope="col" class="px-6 py-4 font-medium">Expires</th></tr></thead><tbody class="divide-y divide-gray-100 text-gray-700 dark:divide-gray-800 dark:text-gray-300">
                    @forelse ($invitations as $invitation)
                        <tr><td class="px-6 py-4 font-medium">{{ $invitation->email }}</td><td class="px-6 py-4">{{ $invitation->role->label() }}</td><td class="px-6 py-4"><span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300">{{ $invitation->status === \App\Enums\TeamInvitationStatus::Pending && ! $invitation->isPending() ? 'Expired' : $invitation->status->label() }}</span></td><td class="px-6 py-4 text-gray-500 dark:text-gray-400">{{ $invitation->expires_at->format('M j, Y, g:i A') }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">No invitations yet. Invite a colleague to start collaborating.</td></tr>
                    @endforelse
                </tbody></table></div>
                <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $invitations->links() }}</div>
            </section>
            <section aria-labelledby="permissions-heading" class="rounded-2xl border border-gray-200 bg-white p-5 sm:p-6 dark:border-gray-800 dark:bg-white/[0.03]">
                <h2 id="permissions-heading" class="text-lg font-semibold text-gray-800 dark:text-white/90">Role permissions</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Access follows each member's role and job assignments.</p>
                <dl class="mt-5 divide-y divide-gray-100 dark:divide-gray-800">@foreach (\App\Enums\OrganizationRole::cases() as $role)<div class="grid gap-2 py-4 sm:grid-cols-4"><dt class="text-sm font-medium text-gray-800 dark:text-white/90">{{ $role->label() }}</dt><dd class="text-sm leading-6 text-gray-500 sm:col-span-3 dark:text-gray-400">{{ $roleDescriptions[$role->value] }}</dd></div>@endforeach</dl>
            </section>
            <dialog x-ref="inviteDialog" aria-labelledby="invite-heading" @if ($errors->any() && old('_team_form') === 'invite') x-init="$el.showModal()" @endif class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 text-gray-800 shadow-xl backdrop:bg-gray-900/50 sm:p-8 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                <form action="{{ route('employer.team.invitations.store') }}" method="POST" class="flex flex-col gap-5">
                    @csrf<input type="hidden" name="_team_form" value="invite">
                    <div class="flex items-start justify-between gap-3"><div><h2 id="invite-heading" class="text-lg font-semibold">Invite team member</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Send a secure invitation to your colleague.</p></div><button type="button" @click="$refs.inviteDialog.close()" aria-label="Close invitation dialog" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xl text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800">&times;</button></div>
                    <div><label for="invite-email" class="mb-2 block text-sm font-medium">Email address</label><input id="invite-email" name="email" type="email" value="{{ old('_team_form') === 'invite' ? old('email') : '' }}" required maxlength="255" autocomplete="email" autofocus class="min-h-[44px] w-full rounded-lg border-gray-300 bg-transparent text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700">@error('email')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror</div>
                    <div><label for="invite-role" class="mb-2 block text-sm font-medium">Role</label><select id="invite-role" name="role" required class="min-h-[44px] w-full rounded-lg border-gray-300 bg-white text-base focus:border-blue-500 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-900"><option value="">Choose a role</option>@foreach ($roleOptions as $role)<option value="{{ $role->value }}" @selected(old('_team_form') === 'invite' && old('role') === $role->value)>{{ $role->label() }}</option>@endforeach</select>@error('role')<p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror</div>
                    <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" @click="$refs.inviteDialog.close()" class="min-h-[44px] rounded-lg border border-gray-300 px-4 text-sm font-medium dark:border-gray-700">Cancel</button><button type="submit" class="min-h-[44px] rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Send invitation</button></div>
                </form>
            </dialog>
            @foreach ($members as $member)
                @can('update', $member)
                    <dialog id="edit-member-{{ $member->id }}" aria-labelledby="edit-member-heading-{{ $member->id }}" @if ($errors->any() && old('_team_form') === 'member-'.$member->id) x-init="$el.showModal()" @endif class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 text-gray-800 shadow-xl backdrop:bg-gray-900/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <form method="POST" action="{{ route('employer.team.members.update', $member) }}" class="flex flex-col gap-5">@csrf @method('PATCH')<input type="hidden" name="_team_form" value="member-{{ $member->id }}">
                            <div><h2 id="edit-member-heading-{{ $member->id }}" class="text-lg font-semibold">Edit member access</h2><p class="mt-1 break-all text-sm text-gray-500 dark:text-gray-400">{{ $member->user?->email }}</p></div>
                            <div><label for="member-role-{{ $member->id }}" class="mb-2 block text-sm font-medium">Role</label><select id="member-role-{{ $member->id }}" name="role" required class="min-h-[44px] w-full rounded-lg border-gray-300 bg-white text-base dark:border-gray-700 dark:bg-gray-900">@foreach ($roleOptions as $role)<option value="{{ $role->value }}" @selected((old('_team_form') === 'member-'.$member->id ? old('role') : $member->role->value) === $role->value)>{{ $role->label() }}</option>@endforeach</select></div>
                            <div><label for="member-status-{{ $member->id }}" class="mb-2 block text-sm font-medium">Status</label><select id="member-status-{{ $member->id }}" name="status" required class="min-h-[44px] w-full rounded-lg border-gray-300 bg-white text-base dark:border-gray-700 dark:bg-gray-900">@foreach (\App\Enums\OrganizationMemberStatus::cases() as $status)<option value="{{ $status->value }}" @selected((old('_team_form') === 'member-'.$member->id ? old('status') : $member->status->value) === $status->value)>{{ $status->label() }}</option>@endforeach</select><p class="mt-2 text-xs leading-5 text-gray-500 dark:text-gray-400">Suspended members immediately lose access to this organization.</p></div>
                            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 pt-5 sm:flex-row sm:justify-end dark:border-gray-800"><button type="button" @click="$el.closest('dialog').close()" class="min-h-[44px] rounded-lg border border-gray-300 px-4 text-sm font-medium dark:border-gray-700">Cancel</button><button type="submit" class="min-h-[44px] rounded-lg bg-blue-600 px-5 text-sm font-medium text-white hover:bg-blue-700">Save access</button></div>
                        </form>
                    </dialog>
                    <dialog id="remove-member-{{ $member->id }}" aria-labelledby="remove-member-heading-{{ $member->id }}" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] max-w-lg overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 text-gray-800 shadow-xl backdrop:bg-gray-900/50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <form method="POST" action="{{ route('employer.team.members.destroy', $member) }}" class="flex flex-col gap-5">@csrf @method('DELETE')<h2 id="remove-member-heading-{{ $member->id }}" class="text-lg font-semibold">Remove team member?</h2><p class="text-sm leading-6 text-gray-500 dark:text-gray-400">{{ $member->user?->email }} will lose access to this organization and its assigned jobs. Their account remains available.</p><div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" @click="$el.closest('dialog').close()" class="min-h-[44px] rounded-lg border border-gray-300 px-4 text-sm font-medium dark:border-gray-700">Cancel</button><button type="submit" class="min-h-[44px] rounded-lg bg-red-600 px-5 text-sm font-medium text-white hover:bg-red-700">Remove member</button></div></form>
                    </dialog>
                @endcan
            @endforeach
        </div>
    </main>
</x-layout>
