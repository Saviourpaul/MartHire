@include('errors.recovery', [
    'status' => 419,
    'title' => __('expiration.session_title'),
    'message' => __('expiration.session_message'),
    'help' => __('expiration.session_help'),
    'actionUrl' => auth()->check() ? route('dashboard') : route('login'),
    'actionLabel' => auth()->check() ? __('expiration.dashboard_action') : __('expiration.sign_in_action'),
])
