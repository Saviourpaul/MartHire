<x-app-layout>
    <x-slot name="title">{{ __('Review :company', ['company' => $demoRequest->company_name]) }}</x-slot>
    <x-slot name="header">
        <div class="nw-demo-review-heading">
            <div>
                <a class="nw-link nw-link--small" href="{{ route('admin.demo-requests.index') }}"><x-icon name="arrow-left" /> {{ __('All demo requests') }}</a>
                <h1 class="nw-heading">{{ $demoRequest->company_name }}</h1>
                <p>{{ __('Received :date', ['date' => $demoRequest->created_at->format('j M Y, g:i A')]) }}</p>
            </div>
            <div class="nw-demo-review-heading__badges">
                <x-status-badge :status="$demoRequest->status->label()" />
                <x-status-badge :status="$demoRequest->setup_expires_at?->isPast() && $demoRequest->onboarding_status->value === 'invitation_sent' ? __('Invitation expired') : $demoRequest->onboarding_status->label()" />
            </div>
        </div>
    </x-slot>

    @if ($errors->any())
        <div class="nw-notice nw-notice--error" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="nw-demo-review-grid">
        <div class="nw-demo-review-main">
            <section class="nw-panel nw-demo-review-card" aria-labelledby="demo-contact-heading">
                <header class="nw-panel__head"><div><h2 id="demo-contact-heading">{{ __('Request details') }}</h2><p>{{ __('Business and contact information supplied by the requester.') }}</p></div></header>
                <dl class="nw-demo-review-details">
                    <div><dt>{{ __('Contact person') }}</dt><dd>{{ trim($demoRequest->contact_first_name.' '.$demoRequest->contact_last_name) }}</dd></div>
                    <div><dt>{{ __('Business email') }}</dt><dd><a href="mailto:{{ $demoRequest->business_email }}">{{ $demoRequest->business_email }}</a></dd></div>
                    <div><dt>{{ __('Phone') }}</dt><dd><a href="tel:{{ $demoRequest->phone }}">{{ $demoRequest->phone }}</a></dd></div>
                    @if ($demoRequest->industry)<div><dt>{{ __('Industry') }}</dt><dd>{{ $demoRequest->industry }}</dd></div>@endif
                    <div><dt>{{ __('Organization size') }}</dt><dd>{{ $demoRequest->organization_size }} {{ __('employees') }}</dd></div>
                    @if ($demoRequest->demo_scheduled_at)
                        <div><dt>{{ __('Demo appointment') }}</dt><dd>{{ $demoRequest->demo_scheduled_at->timezone($demoRequest->demo_timezone ?: config('app.timezone'))->format('j M Y, g:i A') }} {{ $demoRequest->demo_timezone }}</dd></div>
                    @endif
                    @if ($demoRequest->assignedAdmin)
                        <div><dt>{{ __('Assigned reviewer') }}</dt><dd>{{ trim($demoRequest->assignedAdmin->first_name.' '.$demoRequest->assignedAdmin->last_name) }}</dd></div>
                    @endif
                    @if ($demoRequest->convertedOrganization)
                        <div><dt>{{ __('Created organization') }}</dt><dd>{{ $demoRequest->convertedOrganization->name }}</dd></div>
                    @endif
                </dl>
                @if ($demoRequest->rejection_reason)
                    <div class="nw-notice nw-notice--warning"><strong>{{ __('Rejection reason') }}</strong><p>{{ $demoRequest->rejection_reason }}</p></div>
                @endif
                @if ($demoRequest->risk_flags)
                    <div class="nw-notice nw-notice--warning">
                        <strong>{{ __('Verification signals') }}</strong>
                        <ul>
                            @foreach ($demoRequest->risk_flags as $riskFlag)
                                <li>{{ str($riskFlag)->replace('_', ' ')->headline() }}</li>
                            @endforeach
                        </ul>
                        @if ($demoRequest->possibleDuplicateOf)
                            <a class="nw-link nw-link--small" href="{{ route('admin.demo-requests.show', $demoRequest->possibleDuplicateOf) }}">{{ __('Review the possible match') }}</a>
                        @endif
                    </div>
                @endif
            </section>

            @if ($demoRequest->requester_information_response)
                <section class="nw-panel nw-demo-review-card" aria-labelledby="requester-response-heading">
                    <header class="nw-panel__head"><div><h2 id="requester-response-heading">{{ __('Additional information from requester') }}</h2><p>{{ __('Submitted :date', ['date' => $demoRequest->requester_information_received_at?->format('j M Y, g:i A')]) }}</p></div></header>
                    <p>{{ $demoRequest->requester_information_response }}</p>
                </section>
            @endif

            @if ($demoRequest->internal_notes)
                <section class="nw-panel nw-demo-review-card" aria-labelledby="internal-notes-heading">
                    <header class="nw-panel__head"><div><h2 id="internal-notes-heading">{{ __('Internal notes') }}</h2><p>{{ __('Visible to administrators only.') }}</p></div></header>
                    <pre class="nw-demo-review-notes">{{ $demoRequest->internal_notes }}</pre>
                </section>
            @endif

            <section class="nw-panel nw-demo-review-card" aria-labelledby="request-history-heading">
                <header class="nw-panel__head"><div><h2 id="request-history-heading">{{ __('Request history') }}</h2><p>{{ __('Administrative and onboarding events.') }}</p></div></header>
                @if ($demoRequest->events->isEmpty())
                    <p class="nw-hint">{{ __('No history recorded.') }}</p>
                @else
                    <ol class="nw-demo-review-events">
                        @foreach ($demoRequest->events as $event)
                            <li>
                                <span class="nw-demo-review-events__dot" aria-hidden="true"></span>
                                <div><strong>{{ str($event->event)->replace('_', ' ')->headline() }}</strong>
                                    <small>{{ $event->actor ? trim($event->actor->first_name.' '.$event->actor->last_name) : __('System') }} · {{ $event->created_at->format('j M Y, g:i A') }}</small>
                                    @if (filled($event->details['reason'] ?? null))<p>{{ $event->details['reason'] }}</p>@endif
                                    @if (filled($event->details['note'] ?? null))<p>{{ $event->details['note'] }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </section>
        </div>

        <aside class="nw-demo-review-actions" aria-label="{{ __('Review actions') }}">
            @if (in_array($demoRequest->status->value, ['submitted', 'under_review', 'information_requested', 'guided_demo_offered'], true))
                <section class="nw-panel nw-demo-review-card">
                    <h2>{{ __('Review actions') }}</h2>
                    @if (! $demoRequest->assigned_admin_id || $demoRequest->assigned_admin_id === auth()->id())
                        <form method="POST" action="{{ route('admin.demo-requests.claim', $demoRequest) }}">@csrf<button class="nw-button nw-button--outline nw-button--block" type="submit">{{ $demoRequest->assigned_admin_id ? __('Keep this request assigned to me') : __('Assign to me') }}</button></form>
                    @endif

                    @if ($guidedDemoBookingConfigured && $demoRequest->status->value !== 'guided_demo_offered')
                        <form method="POST" action="{{ route('admin.demo-requests.guided-demo', $demoRequest) }}" data-confirm="{{ __('Send the organization the MarHire guided demo booking link?') }}" data-confirm-label="{{ __('Send booking link') }}">
                            @csrf
                            <button class="nw-button nw-button--outline nw-button--block" type="submit">{{ __('Offer guided demo') }}</button>
                        </form>
                    @elseif ($demoRequest->status->value === 'guided_demo_offered')
                        <p class="nw-hint">{{ __('The guided demo booking link has already been sent.') }}</p>
                    @else
                        <p class="nw-hint">{{ __('Guided demo booking is unavailable until its secure booking URL is configured.') }}</p>
                    @endif

                    @if ($demoRequest->status->value !== 'information_requested')
                        <form method="POST" action="{{ route('admin.demo-requests.request-information', $demoRequest) }}" class="nw-form-stack nw-demo-review-form">
                            @csrf
                            <h3>{{ __('Request more information') }}</h3>
                            <label class="nw-label" for="demo-information-message">{{ __('What details should the requester provide?') }}</label>
                            <textarea class="nw-input" id="demo-information-message" name="message" rows="3" maxlength="2000" required>{{ old('message') }}</textarea>
                            <button class="nw-button nw-button--outline nw-button--small" type="submit">{{ __('Email request for information') }}</button>
                        </form>
                    @else
                        <p class="nw-hint">{{ __('This request is waiting for information from the requester.') }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.demo-requests.notes.store', $demoRequest) }}" class="nw-form-stack nw-demo-review-form">
                        @csrf
                        <h3>{{ __('Add internal note') }}</h3>
                        <textarea class="nw-input" name="note" rows="3" maxlength="4000" required placeholder="{{ __('Notes for other administrators') }}">{{ old('note') }}</textarea>
                        <button class="nw-button nw-button--outline nw-button--small" type="submit">{{ __('Save note') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.demo-requests.approve', $demoRequest) }}" data-confirm="{{ __('Approve this request and email a one-time account setup invitation?') }}" data-confirm-label="{{ __('Approve and invite') }}">@csrf<button class="nw-button nw-button--primary nw-button--block" type="submit">{{ __('Approve and send setup invitation') }}</button></form>

                    <form id="reject-request" method="POST" action="{{ route('admin.demo-requests.reject', $demoRequest) }}" class="nw-form-stack nw-demo-review-form" data-confirm="{{ __('Reject this organization request?') }}" data-confirm-label="{{ __('Reject request') }}">
                        @csrf
                        <h3>{{ __('Reject request') }}</h3>
                        <label class="nw-label" for="demo-rejection-reason">{{ __('Internal reason for administrative records') }}</label>
                        <textarea class="nw-input" id="demo-rejection-reason" name="reason" rows="3" maxlength="2000" required placeholder="{{ __('Record why this request is being declined') }}">{{ old('reason') }}</textarea>
                        <button class="nw-button nw-button--danger nw-button--small" type="submit">{{ __('Reject request') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.demo-requests.duplicate', $demoRequest) }}" class="nw-form-stack nw-demo-review-form" data-confirm="{{ __('Mark this request as a duplicate?') }}" data-confirm-label="{{ __('Mark as duplicate') }}">
                        @csrf
                        <textarea class="nw-input" name="note" rows="2" maxlength="2000" placeholder="{{ __('Optional duplicate reference or note') }}">{{ old('note') }}</textarea>
                        <button class="nw-button nw-button--outline nw-button--small" type="submit">{{ __('Mark as duplicate') }}</button>
                    </form>
                </section>
            @elseif ($demoRequest->status->value === 'approved' && ! $demoRequest->setup_accepted_at)
                <section class="nw-panel nw-demo-review-card">
                    <h2>{{ __('Account setup') }}</h2>
                    <p>{{ __('The request is approved. The contact creates their password after accepting the secure invitation.') }}</p>
                    <p><strong>{{ __('Invitation status') }}:</strong> {{ $demoRequest->setup_expires_at?->isPast() && $demoRequest->onboarding_status->value === 'invitation_sent' ? __('Expired') : $demoRequest->onboarding_status->label() }}</p>
                    @if ($demoRequest->setup_sent_at)<p class="nw-hint">{{ __('Last sent :date', ['date' => $demoRequest->setup_sent_at->format('j M Y, g:i A')]) }}</p>@endif
                    <form method="POST" action="{{ route('admin.demo-requests.resend', $demoRequest) }}">@csrf<button class="nw-button nw-button--primary nw-button--block" type="submit">{{ __('Send or resend invitation') }}</button></form>
                </section>
            @elseif ($demoRequest->convertedUser)
                <section class="nw-panel nw-demo-review-card">
                    <h2>{{ __('Organization account') }}</h2>
                    <p>{{ __('Owner') }}: {{ trim($demoRequest->convertedUser->first_name.' '.$demoRequest->convertedUser->last_name) }}</p>
                    <a class="nw-link" href="{{ route('admin.users.organizations') }}">{{ __('View organization accounts') }} <x-icon name="arrow-right" /></a>
                </section>
            @endif
        </aside>
    </div>
</x-app-layout>
