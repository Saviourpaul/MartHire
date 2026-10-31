@php
    $notifications = [];
    $seen = [];
    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $message = session($type);
        if (is_string($message) && trim($message) !== '' && ! isset($seen[$message])) {
            $notifications[] = ['type' => $type, 'message' => $message];
            $seen[$message] = true;
        }
    }

    $status = session('status');
    if (is_string($status) && trim($status) !== '') {
        [$type, $message] = match ($status) {
            'password-updated' => ['success', __('Your password has been updated.')],
            'profile-updated' => ['success', __('Profile updated successfully.')],
            'verification-code-sent' => ['success', __('A new verification code has been sent to your email address.')],
            'verification-link-sent' => ['success', __('A new verification link has been sent to your email address.')],
            'verification-required' => ['warning', __('Please verify your email address to continue.')],
            default => ['info', $status],
        };
        if (! isset($seen[$message])) {
            $notifications[] = ['type' => $type, 'message' => $message];
        }
    }
@endphp

    <section class="nw-toasts" data-position="bottom-right" data-toast-region aria-label="{{ __('Notifications') }}">
        <ol class="nw-toasts__list" data-toast-list>
            @foreach ($notifications as $notification)
                <x-toast :type="$notification['type']" :message="$notification['message']" />
            @endforeach
        </ol>
    </section>
<x-alert-dialog />
@if (is_array(session('live-table-topics')))
    <span hidden data-live-changes="{{ json_encode(session('live-table-topics')) }}"></span>
@endif
