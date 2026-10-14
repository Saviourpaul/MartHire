@include('errors.recovery', [
    'status' => 410,
    'title' => __('expiration.link_title'),
    'message' => __('expiration.link_message'),
    'help' => __('expiration.link_help'),
    'actionUrl' => route('home'),
    'actionLabel' => __('expiration.home_action'),
])
