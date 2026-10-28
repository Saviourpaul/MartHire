<x-app-layout>
    <x-slot name="title">{{ __('Hiring team') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Hiring team') }}</h1>
        <p>{{ __('Invite teammates and manage who can work on your hiring.') }}</p>
    </x-slot>

    @if (session('success'))
        <div class="nw-notice" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    @if (in_array($currentOrganizationRole, [\App\Enums\OrganizationRole::Owner, \App\Enums\OrganizationRole::HrManager], true))
        <section class="nw-panel" aria-labelledby="invite-heading">
            <header class="nw-panel__head">
                <div>
                    <h2 id="invite-heading">{{ __('Invite a teammate') }}</h2>
                    <p>{{ __('They’ll get an email with a link to join.') }}</p>
                </div>
            </header>

            <form method="POST" action="{{ route('organisations.team.invitations.store') }}" class="nw-form-row nw-form-row--fields">
                @csrf
                <div>
                    <x-input-label for="invite-email" :value="__('Email')" />
                    <x-text-input id="invite-email" type="email" name="email" :value="old('email')" required />
                    <x-input-error id="invite-email-error" :messages="$errors->get('email')" />
                </div>
                <div>
                    <x-input-label for="invite-role" :value="__('Role')" />
                    <select id="invite-role" class="nw-select" name="role" required>
                        @foreach ($roleOptions as $roleOption)
                            <option value="{{ $roleOption->value }}" @selected(old('role') === $roleOption->value)>{{ $roleOption->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error id="invite-role-error" :messages="$errors->get('role')" />
                </div>
                <x-primary-button>{{ __('Send invitation') }}</x-primary-button>
            </form>
        </section>
    @endif

    <section class="nw-panel" aria-labelledby="invitations-heading">
        <header class="nw-panel__head">
            <div>
                <h2 id="invitations-heading">{{ __('Pending invitations') }}</h2>
            </div>
        </header>

        @if ($invitations->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No pending invitations.') }}</h3>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Email') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Invited by') }}</th>
                            <th scope="col">{{ __('Expires') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($invitations as $invitation)
                            <tr>
                                <td class="nw-table__main">{{ $invitation->email }}</td>
                                <td><x-status-badge :status="$invitation->role->label()" /></td>
                                <td>{{ trim(($invitation->invitedBy?->first_name ?? '').' '.($invitation->invitedBy?->last_name ?? '')) ?: '—' }}</td>
                                <td>{{ $invitation->expires_at->format('j M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nw-pagination">{{ $invitations->onEachSide(1)->links() }}</div>
        @endif
    </section>

    <section class="nw-panel" aria-labelledby="members-heading">
        <header class="nw-panel__head">
            <div>
                <h2 id="members-heading">{{ __('Members') }}</h2>
            </div>
        </header>

        @if ($members->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No team members yet.') }}</h3>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Name') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Jobs assigned') }}</th>
                            <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($members as $member)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <span class="nw-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($member->user->first_name, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ trim($member->user->first_name.' '.$member->user->last_name) }}</strong>
                                            <small>{{ $member->user->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td><x-status-badge :status="$member->role->label()" /></td>
                                <td><x-status-badge :status="$member->status" /></td>
                                <td>{{ $member->jobs_count }}</td>
                                <td class="nw-table__go">
                                    <button type="button" class="nw-link nw-link--small" x-on:click="$dispatch('open-modal', 'manage-member-{{ $member->id }}')">
                                        {{ __('Manage') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nw-pagination">{{ $members->onEachSide(1)->links() }}</div>
        @endif
    </section>

    @foreach ($members as $member)
        <x-modal :name="'manage-member-'.$member->id">
            <div class="nw-modal-content">
                <div class="nw-modal-profile">
                    <img src="{{ $member->user->profileImageUrl() }}" alt="" class="nw-modal-profile__image">
                    <div>
                        <h2 id="manage-member-{{ $member->id }}-title" class="nw-modal-profile__name">{{ trim($member->user->first_name.' '.$member->user->last_name) }}</h2>
                        <p id="manage-member-{{ $member->id }}-description" class="nw-modal-profile__email">{{ $member->user->email }}</p>
                        <div class="nw-modal-profile__badges">
                            <x-status-badge :status="$member->role->label()" />
                            <x-status-badge :status="$member->status" />
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('organisations.team.members.update', $member) }}">
                    @csrf
                    @method('PATCH')
                    <div class="nw-form-row nw-form-row--fields">
                        <div>
                            <x-input-label :for="'role-'.$member->id" :value="__('Role')" />
                            <select id="role-{{ $member->id }}" class="nw-select" name="role" required>
                                @foreach ($roleOptions as $roleOption)
                                    <option value="{{ $roleOption->value }}" @selected($member->role->value === $roleOption->value)>{{ $roleOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label :for="'status-'.$member->id" :value="__('Status')" />
                            <select id="status-{{ $member->id }}" class="nw-select" name="status" required>
                                @foreach (\App\Enums\OrganizationMemberStatus::cases() as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected($member->status->value === $statusOption->value)>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="nw-form-row nw-modal-actions">
                        <button type="button" class="nw-link" x-on:click="$dispatch('close-modal', 'manage-member-{{ $member->id }}')">{{ __('Cancel') }}</button>
                        <x-primary-button><x-icon name="check" /> {{ __('Save changes') }}</x-primary-button>
                    </div>
                </form>

                @unless ($member->user->is(auth()->user()))
                    <div class="nw-modal-actions nw-modal-actions--secondary">
                        <form method="POST" action="{{ route('organisations.team.members.destroy', $member) }}" onsubmit="return confirm('{{ __('Remove this team member? Their job assignments will be cleared.') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nw-button nw-button--danger nw-button--small">
                                <x-icon name="trash" /> {{ __('Remove from team') }}
                            </button>
                        </form>
                    </div>
                @endunless
            </div>
        </x-modal>
    @endforeach
</x-app-layout>
