<x-app-layout>
    <x-slot name="title">{{ $title }}</x-slot>
    @fragment('admin-users')
    <div data-live-table="admin-users" data-live-topics="users,team,candidates,jobs" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
    <header class="nw-page-heading nw-page-heading--app">
        <div>
        <h1 class="nw-heading">{{ $title }}</h1>
        <p>{{ __('Manage platform accounts.') }}</p>
        </div>
    </header>
    <form method="GET" action="{{ request()->url() }}" role="search" data-live-filter>
        <x-table.toolbar :tabs-label="__('User categories')">
            <x-slot:tabs>
                <a class="nw-segmented__item{{ request()->routeIs('admin.users.organizations') ? ' is-active' : '' }}" href="{{ route('admin.users.organizations') }}" data-live-link>{{ __('Organizations') }}</a>
                <a class="nw-segmented__item{{ request()->routeIs('admin.users.applicants') ? ' is-active' : '' }}" href="{{ route('admin.users.applicants') }}" data-live-link>{{ __('Applicants') }}</a>
                <a class="nw-segmented__item{{ request()->routeIs('admin.users.administrators') ? ' is-active' : '' }}" href="{{ route('admin.users.administrators') }}" data-live-link>{{ __('Admins') }}</a>
                <a class="nw-segmented__item{{ request()->routeIs('admin.users.suspended') ? ' is-active' : '' }}" href="{{ route('admin.users.suspended') }}" data-live-link>{{ __('Suspended') }}</a>
            </x-slot:tabs>

            <div class="nw-search-field">
                <x-icon name="search" />
                <label class="nw-sr-only" for="user-search">{{ __('Search users') }}</label>
                <input id="user-search" class="nw-input" type="search" name="search" placeholder="{{ __('Search users…') }}" value="{{ $filterValues['search'] }}">
            </div>

            <x-slot:filters>
                @unless ($roleConstraint)
                    <select class="nw-select" name="role" aria-label="{{ __('Filter by role') }}">
                        <option value="">{{ __('All roles') }}</option>
                        @foreach ($roles as $roleOption)
                            <option value="{{ $roleOption->value }}" @selected($filterValues['role'] === $roleOption->value)>{{ $roleOption->label() }}</option>
                        @endforeach
                    </select>
                @endunless
                <select class="nw-select" name="sort" aria-label="{{ __('Sort users by') }}">
                    @foreach (['created_at' => __('Joined'), 'first_name' => __('First name'), 'last_name' => __('Last name'), 'email' => __('Email'), 'phone' => __('Phone')] as $column => $label)
                        <option value="{{ $column }}" @selected($sortColumn === $column)>{{ $label }}</option>
                    @endforeach
                </select>
                <select class="nw-select" name="direction" aria-label="{{ __('Sort direction') }}">
                    <option value="desc" @selected($sortDirection === 'desc')>{{ __('Descending') }}</option>
                    <option value="asc" @selected($sortDirection === 'asc')>{{ __('Ascending') }}</option>
                </select>

                @unless ($statusConstraint)
                    <select class="nw-select" name="status" aria-label="{{ __('Filter by status') }}">
                        <option value="">{{ __('All statuses') }}</option>
                        @foreach ($statuses as $statusOption)
                            <option value="{{ $statusOption->value }}" @selected($filterValues['status'] === $statusOption->value)>{{ $statusOption->label() }}</option>
                        @endforeach
                    </select>
                @endunless
            </x-slot:filters>

            <x-slot:actions>
                <button type="submit" class="nw-button nw-button--outline nw-button--small">{{ __('Filter') }}</button>
                @if ($filterValues['search'] || $filterValues['role'] || $filterValues['status'] || $filterValues['created_from'] || $filterValues['created_to'])
                    <a href="{{ request()->url() }}" class="nw-link nw-link--small" data-live-link>{{ __('Clear') }}</a>
                @endif
                @if ($exportRoute)
                    <a href="{{ route($exportRoute, request()->query()) }}" class="nw-button nw-button--outline nw-button--small">
                        <x-icon name="download" /> {{ __('Export') }}
                    </a>
                @endif
            </x-slot:actions>
        </x-table.toolbar>
    </form>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="nw-panel" aria-labelledby="users-heading">
        <header class="nw-panel__head nw-sr-only">
            <h2 id="users-heading">{{ $title }}</h2>
        </header>

        @if ($users->isEmpty())
            <div class="nw-empty">
                <h3>{{ __('No users found.') }}</h3>
                <p>{{ __('Try a different search or filter.') }}</p>
            </div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Name') }}</th>
                            <th scope="col">{{ __('Phone') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Status') }}</th>
                            <th scope="col">{{ __('Joined') }}</th>
                            <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $managedUser)
                            <tr>
                                <td class="nw-table__main">
                                    <div class="nw-table__role">
                                        <span class="nw-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($managedUser->first_name, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ trim($managedUser->first_name.' '.$managedUser->last_name) }}</strong>
                                            <small>{{ $managedUser->email }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $managedUser->phone ?: '—' }}</td>
                                <td><x-status-badge :status="$managedUser->role->label()" /></td>
                                <td><x-status-badge :status="$managedUser->status" /></td>
                                <td>{{ $managedUser->created_at->format('j M Y') }}</td>
                                <td class="nw-table__go nw-table__go--persist">
                                    <button type="button" class="nw-link nw-link--small" x-on:click="$dispatch('open-modal', 'manage-user-{{ $managedUser->id }}')">
                                        {{ __('Manage') }}
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="nw-pagination">{{ $users->onEachSide(1)->links() }}</div>
        @endif
    </section>

    @foreach ($users as $managedUser)
        <x-modal :name="'manage-user-'.$managedUser->id">
            <div class="nw-modal-content">
                <div class="nw-modal-profile">
                    <img src="{{ $managedUser->profileImageUrl() }}" alt="" class="nw-modal-profile__image">
                    <div>
                        <h2 id="manage-user-{{ $managedUser->id }}-title" class="nw-modal-profile__name">{{ trim($managedUser->first_name.' '.$managedUser->last_name) }}</h2>
                        <p id="manage-user-{{ $managedUser->id }}-description" class="nw-modal-profile__email">{{ $managedUser->email }}</p>
                        <div class="nw-modal-profile__badges">
                            <x-status-badge :status="$managedUser->role->label()" />
                            <x-status-badge :status="$managedUser->status" />
                        </div>
                    </div>
                </div>

                <dl class="nw-detail-list">
                    <div><dt>{{ __('Phone') }}</dt><dd>{{ $managedUser->phone ?: '—' }}</dd></div>
                    <div><dt>{{ __('Location') }}</dt><dd>{{ collect([$managedUser->city, $managedUser->state])->filter()->implode(', ') }} @if($managedUser->country_code || $managedUser->country)<x-country-display :code="$managedUser->country_code" :name="$managedUser->country" />@elseif(!$managedUser->city && !$managedUser->state) — @endif</dd></div>
                    <div><dt>{{ __('Joined') }}</dt><dd>{{ $managedUser->created_at->format('j M Y') }}</dd></div>
                </dl>

                <form method="POST" action="{{ route('admin.users.update', $managedUser) }}">
                    @csrf
                    @method('PATCH')
                    <div class="nw-form-row nw-form-row--fields">
                        <div>
                            <x-input-label :for="'role-'.$managedUser->id" :value="__('Role')" />
                            <select id="role-{{ $managedUser->id }}" class="nw-select" name="role" required>
                                @foreach ($roles as $roleOption)
                                    <option value="{{ $roleOption->value }}" @selected($managedUser->role->value === $roleOption->value)>{{ $roleOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <x-input-label :for="'status-'.$managedUser->id" :value="__('Status')" />
                            <select id="status-{{ $managedUser->id }}" class="nw-select" name="status" required>
                                @foreach ($statuses as $statusOption)
                                    <option value="{{ $statusOption->value }}" @selected($managedUser->status->value === $statusOption->value)>{{ $statusOption->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <x-form.actions>
                        <x-slot:cancel>
                            <button type="button" class="nw-link" x-on:click="$dispatch('close-modal', 'manage-user-{{ $managedUser->id }}')">{{ __('Cancel') }}</button>
                        </x-slot:cancel>
                        <x-primary-button><x-icon name="check" /> {{ __('Save changes') }}</x-primary-button>
                    </x-form.actions>
                </form>

                <div class="nw-modal-actions nw-modal-actions--secondary">
                    @if ($managedUser->status->value === 'suspended')
                        <form method="POST" action="{{ route('admin.users.activate', $managedUser) }}" data-confirm="{{ __('Reactivate this account?') }}" data-confirm-label="{{ __('Reactivate account') }}" data-confirm-type="info">
                            @csrf
                            <button type="submit" class="nw-button nw-button--outline nw-button--small">
                                <x-icon name="check" /> {{ __('Activate') }}
                            </button>
                        </form>
                    @elseif (! $managedUser->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.suspend', $managedUser) }}" data-confirm="{{ __('Suspend this account?') }}" data-confirm-label="{{ __('Suspend account') }}" data-confirm-type="warning">
                            @csrf
                            <button type="submit" class="nw-button nw-button--outline nw-button--small">
                                <x-icon name="pause" /> {{ __('Suspend') }}
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.users.password-reset', $managedUser) }}">
                        @csrf
                        <button type="submit" class="nw-button nw-button--outline nw-button--small">
                            <x-icon name="key" /> {{ __('Send password reset') }}
                        </button>
                    </form>

                    @unless ($managedUser->is(auth()->user()))
                        <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" data-confirm="{{ __('Permanently delete this account? This cannot be undone.') }}" data-confirm-label="{{ __('Delete account') }}" data-confirm-type="error">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="nw-button nw-button--danger nw-button--small">
                                <x-icon name="trash" /> {{ __('Delete') }}
                            </button>
                        </form>
                    @endunless
                </div>
            </div>
        </x-modal>
    @endforeach
    </div>
    @endfragment
</x-app-layout>
