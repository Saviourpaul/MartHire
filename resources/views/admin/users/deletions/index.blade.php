@use('BackedEnum', 'NativeBackedEnum')
@use('Illuminate\Support\Str')

<x-app-layout>
    <x-slot name="title">{{ __('Account deletion history') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Account deletion history') }}</h1>
        <p>{{ __('Review inactivity warnings, scheduled deletions, permanent account deletions, and retained history.') }}</p>
        <a class="nw-button nw-button--outline nw-button--small" href="{{ route('admin.users.applicants') }}">{{ __('Back to account management') }}</a>
    </x-slot>

    <form method="GET" action="{{ request()->url() }}" class="nw-demo-filters" role="search">
        <div class="nw-demo-filters__field">
            <label class="nw-sr-only" for="deletion-status">{{ __('Filter by lifecycle status') }}</label>
            <select class="nw-select" id="deletion-status" name="status">
                <option value="">{{ __('All lifecycle statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($statusFilter === $status)>{{ __(Str::headline($status)) }}</option>
                @endforeach
            </select>
        </div>
        <button class="nw-button nw-button--outline nw-button--small" type="submit">{{ __('Filter') }}</button>
        @if ($statusFilter)
            <a class="nw-link nw-link--small" href="{{ request()->url() }}">{{ __('Clear') }}</a>
        @endif
    </form>

    @php
        $humanizeLifecycleValue = static fn ($value) => $value instanceof NativeBackedEnum
            ? Str::headline((string) $value->value)
            : ($value ? Str::headline((string) $value) : '—');
    @endphp

    @if ($deletions->isEmpty())
        <section class="nw-panel nw-empty">
            <h2>{{ __('No account deletion records found') }}</h2>
            <p>{{ __('Scheduled applicant deletions and their history will appear here.') }}</p>
        </section>
    @else
        <section class="nw-panel" aria-label="{{ __('Account deletion records') }}">
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Reference') }}</th>
                            <th scope="col">{{ __('Account') }}</th>
                            <th scope="col">{{ __('Role / reason') }}</th>
                            <th scope="col">{{ __('Last login') }}</th>
                            <th scope="col">{{ __('Scheduled deletion') }}</th>
                            <th scope="col">{{ __('Warning') }}</th>
                            <th scope="col">{{ __('Lifecycle') }}</th>
                            <th scope="col">{{ __('Purge') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($deletions as $deletion)
                            @php
                                $relatedUser = $deletion->user;
                                $userRole = $relatedUser?->role;
                                $roleLabel = $deletion->role_snapshot
                                    ?? (is_object($userRole) && method_exists($userRole, 'label')
                                        ? $userRole->label()
                                        : (is_object($userRole) ? ($userRole->value ?? null) : $userRole));
                                $warningTone = match ($deletion->warning_status) {
                                    'sent' => 'success',
                                    'queued', 'sending' => 'processing',
                                    'pending' => 'pending',
                                    'failed' => 'danger',
                                    default => 'neutral',
                                };
                                $lifecycleTone = match ($deletion->status) {
                                    'scheduled' => 'pending',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'neutral',
                                };
                                $purgeTone = match ($deletion->purge_status) {
                                    'processing' => 'processing',
                                    'pending' => 'pending',
                                    'complete' => 'success',
                                    'failed' => 'danger',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr>
                                <td class="nw-table__main">
                                    <strong>{{ $deletion->reference }}</strong>
                                    <small>{{ __('Created') }} {{ $deletion->created_at?->format('j M Y, H:i') ?? '—' }}</small>
                                </td>
                                <td>
                                    @if ($relatedUser)
                                        <strong>{{ trim($relatedUser->first_name.' '.$relatedUser->last_name) ?: $relatedUser->email }}</strong>
                                        <small>{{ $relatedUser->email }}</small>
                                    @else
                                        <strong>{{ __('Deleted account') }}</strong>
                                        <small>{{ __('The account is no longer present.') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $roleLabel ? $humanizeLifecycleValue($roleLabel) : __('Applicant') }}</strong>
                                    <small>{{ $humanizeLifecycleValue($deletion->reason) }}</small>
                                </td>
                                <td>{{ $deletion->last_login_at_snapshot?->format('j M Y, H:i') ?? '—' }}</td>
                                <td>{{ $deletion->scheduled_deletion_at?->format('j M Y, H:i') ?? '—' }}</td>
                                <td>
                                    <x-status-badge :status="$humanizeLifecycleValue($deletion->warning_status)" :tone="$warningTone" />
                                    @if ($deletion->warning_queued_at)
                                        <small>{{ __('Queued') }}: {{ $deletion->warning_queued_at->format('j M Y, H:i') }}</small>
                                    @endif
                                    @if ($deletion->warning_sent_at)
                                        <small>{{ __('Sent') }}: {{ $deletion->warning_sent_at->format('j M Y, H:i') }}</small>
                                    @endif
                                    <small>{{ __('Attempts') }}: {{ $deletion->warning_attempts }}</small>
                                    @if ($deletion->warning_error)
                                        <small class="nw-error">{{ $deletion->warning_error }}</small>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$humanizeLifecycleValue($deletion->status)" :tone="$lifecycleTone" />
                                    @if ($deletion->completed_at)
                                        <small>{{ __('Completed') }}: {{ $deletion->completed_at->format('j M Y, H:i') }}</small>
                                    @endif
                                    @if ($deletion->cancelled_at)
                                        <small>{{ __('Cancelled') }}: {{ $deletion->cancelled_at->format('j M Y, H:i') }}</small>
                                    @endif
                                </td>
                                <td>
                                    <x-status-badge :status="$humanizeLifecycleValue($deletion->purge_status)" :tone="$purgeTone" />
                                    <small>{{ __('Attempts') }}: {{ $deletion->purge_attempts }}</small>
                                    @if ($deletion->purge_error)
                                        <small class="nw-error">{{ $deletion->purge_error }}</small>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="nw-pagination">{{ $deletions->onEachSide(1)->links() }}</div>
        </section>
    @endif

    <section class="nw-panel" aria-labelledby="legacy-deletions-heading">
        <header class="nw-panel__head">
            <div>
                <h2 id="legacy-deletions-heading">{{ __('Previously closed accounts') }}</h2>
                <p>{{ __(' Permanently deleting one anonymizes application history and removes the account data.') }}</p>
            </div>
        </header>

        @if ($softDeletedUsers->isEmpty())
            <div class="nw-empty"><p>{{ __('There are no previously closed accounts waiting for permanent deletion.') }}</p></div>
        @else
            <div class="nw-table-wrap">
                <table class="nw-table">
                    <thead>
                        <tr>
                            <th scope="col">{{ __('Account') }}</th>
                            <th scope="col">{{ __('Role') }}</th>
                            <th scope="col">{{ __('Closed') }}</th>
                            <th scope="col">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($softDeletedUsers as $softDeletedUser)
                            <tr>
                                <td class="nw-table__main">
                                    <strong>{{ trim($softDeletedUser->first_name.' '.$softDeletedUser->last_name) ?: __('Unnamed account') }}</strong>
                                    <small>{{ $softDeletedUser->email }}</small>
                                </td>
                                <td>{{ $softDeletedUser->role?->label() ?? '—' }}</td>
                                <td>{{ $softDeletedUser->deleted_at?->format('j M Y, H:i') ?? '—' }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.users.destroy', $softDeletedUser->id) }}"
                                        data-confirm="{{ __('Permanently delete this account? Application status and interview history will be kept with personal details anonymized. This cannot be undone.') }}"
                                        data-confirm-label="{{ __('Delete account permanently') }}" data-confirm-type="error">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="nw-button nw-button--danger nw-button--small">
                                            <x-icon name="trash" /> {{ __('Delete permanently') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="nw-pagination">{{ $softDeletedUsers->onEachSide(1)->links() }}</div>
        @endif
    </section>
</x-app-layout>
