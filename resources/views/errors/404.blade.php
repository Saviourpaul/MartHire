@include('errors.recovery', [
    'status' => 404,
    'title' => __('Page not found'),
    'message' => __('This page could not be found.'),
    'help' => __('Check the address or return to the home page.'),
    'actionUrl' => route('home'),
    'actionLabel' => __('expiration.home_action'),
])
