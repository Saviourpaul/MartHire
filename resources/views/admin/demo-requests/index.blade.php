<x-app-layout>
    <x-slot name="title">{{ __('Organization demo requests') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Organization demo requests') }}</h1>
        <p>{{ __('Review organization leads and manage approved account setup invitations.') }}</p>
    </x-slot>

    @fragment('organization-demo-requests')
    <div data-live-table="organization-demo-requests" data-live-topics="demo-requests" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
        <form method="GET" action="{{ route('admin.demo-requests.index') }}" class="nw-demo-filters" data-live-filter>
            <div class="nw-demo-filters__field">
                <label class="nw-sr-only" for="demo-request-search">{{ __('Search demo requests') }}</label>
                <input class="nw-input" id="demo-request-search" type="search" name="search" value="{{ $search }}" placeholder="{{ __('Company, contact, or business email') }}">
            </div>
            <div class="nw-demo-filters__field">
                <label class="nw-sr-only" for="demo-request-status">{{ __('Filter by review status') }}</label>
                <select class="nw-select" id="demo-request-status" name="status">
                    <option value="">{{ __('All review statuses') }}</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($statusFilter === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button class="nw-button nw-button--outline nw-button--small" type="submit">{{ __('Filter') }}</button>
            @if ($search || $statusFilter)
                <a class="nw-link nw-link--small" href="{{ route('admin.demo-requests.index') }}" data-live-link>{{ __('Clear') }}</a>
            @endif
        </form>

        @if ($requests->isEmpty())
            <section class="nw-panel nw-empty">
                <h2>{{ __('No demo requests found') }}</h2>
                <p>{{ __('New organization requests will appear here for administrative review.') }}</p>
            </section>
        @else
            <section class="nw-panel" aria-label="{{ __('Organization demo requests') }}">
                <div class="nw-table-wrap">
                    <table class="nw-table">
                        <thead>
                            <tr>
                                <th scope="col">{{ __('Organization') }}</th>
                                <th scope="col">{{ __('Contact') }}</th>
                                <th scope="col">{{ __('Review') }}</th>
                                <th scope="col">{{ __('Account setup') }}</th>
                                <th scope="col">{{ __('Assigned to') }}</th>
                                <th scope="col">{{ __('Received') }}</th>
                                <th scope="col"><span class="nw-sr-only">{{ __('Actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($requests as $demoRequest)
                                <tr>
                                    <td class="nw-table__main">
                                        <strong>{{ $demoRequest->company_name }}</strong>
                                        <small>{{ $demoRequest->industry ? $demoRequest->industry.' · ' : '' }}{{ $demoRequest->organization_size }} {{ __('employees') }}</small>
                                        @if ($demoRequest->risk_flags)
                                            <small>{{ __('Additional verification suggested') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ trim($demoRequest->contact_first_name.' '.$demoRequest->contact_last_name) }}</strong>
                                        <small>{{ $demoRequest->business_email }}</small>
                                    </td>
                                    <td><x-status-badge :status="$demoRequest->status->label()" /></td>
                                    <td><x-status-badge :status="$demoRequest->setup_expires_at?->isPast() && $demoRequest->onboarding_status->value === 'invitation_sent' ? __('Invitation expired') : $demoRequest->onboarding_status->label()" /></td>
                                    <td>{{ $demoRequest->assignedAdmin ? trim($demoRequest->assignedAdmin->first_name.' '.$demoRequest->assignedAdmin->last_name) : __('Unassigned') }}</td>
                                    <td>{{ $demoRequest->created_at->format('j M Y') }}</td>
                                    <td class="nw-table__go nw-table__go--persist nw-table__actions-cell">
                                        <x-table.actions :label="$demoRequest->company_name">
                                            <a class="nw-row-actions__item" role="menuitem" href="{{ route('admin.demo-requests.show', $demoRequest) }}">
                                                <x-icon name="eye" /> {{ __('View') }}
                                            </a>
                                            @if (in_array($demoRequest->status->value, ['submitted', 'under_review', 'information_requested', 'guided_demo_offered'], true))
                                                <a class="nw-row-actions__item nw-row-actions__item--danger" role="menuitem" href="{{ route('admin.demo-requests.show', $demoRequest) }}#reject-request">
                                                    <x-icon name="close" /> {{ __('Reject request') }}
                                                </a>
                                            @endif
                                        </x-table.actions>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="nw-pagination">{{ $requests->onEachSide(1)->links() }}</div>
            </section>
        @endif
    </div>
    @endfragment
</x-app-layout>
