<x-app-layout>
    <x-slot name="title">{{ __('Notifications') }}</x-slot>
    <x-slot name="header">
        <h1 class="nw-heading">{{ __('Notifications') }}</h1>
        <p>{{ __('Updates about your account and recruitment activity.') }}</p>
    </x-slot>

    @fragment('notification-inbox')
        <div data-live-table="notification-inbox" data-live-topics="notifications" data-live-pages="page" data-live-interval="30000" data-live-url="{{ request()->fullUrl() }}">
            <section class="nw-panel nw-notification-inbox" aria-labelledby="notification-inbox-heading">
                <header class="nw-panel__head">
                    <div>
                        <h2 id="notification-inbox-heading">{{ __('Your inbox') }}</h2>
                        <p>{{ trans_choice(':count unread notification|:count unread notifications', $unreadNotificationCount, ['count' => $unreadNotificationCount]) }}</p>
                    </div>
                    @if ($unreadNotificationCount > 0)
                        <form method="POST" action="{{ route('notifications.read-all') }}">
                            @csrf
                            @method('PATCH')
                            <button class="nw-button nw-button--secondary nw-button--small" type="submit">{{ __('Mark all as read') }}</button>
                        </form>
                    @endif
                </header>

                @if ($notificationItems->isEmpty())
                    <div class="nw-empty">
                        <h3>{{ __('You’re all caught up.') }}</h3>
                        <p>{{ __('New account and hiring updates will appear here.') }}</p>
                    </div>
                @else
                    <div class="nw-notification-list">
                        @foreach ($notificationItems as $item)
                            <article class="nw-notification-item{{ $item['read_at'] ? '' : ' is-unread' }}">
                                <div class="nw-notification-item__content">
                                    <div class="nw-notification-item__heading">
                                        <h3>{{ $item['title'] }}</h3>
                                        <time datetime="{{ $item['created_at']->toIso8601String() }}">{{ $item['created_at']->timezone($displayTimezone)->format('j M Y, g:i A') }}</time>
                                    </div>
                                    <p>{{ $item['message'] }}</p>
                                    @if ($item['action_url'])
                                        <a class="nw-link nw-link--small" href="{{ $item['action_url'] }}">{{ __('View details') }} <x-icon name="arrow-up-right" /></a>
                                    @endif
                                </div>
                                @if (! $item['read_at'])
                                    <form method="POST" action="{{ route('notifications.read', $item['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="nw-button nw-button--secondary nw-button--small" type="submit">{{ __('Mark as read') }}</button>
                                    </form>
                                @endif
                            </article>
                        @endforeach
                    </div>
                    @if ($notifications->hasPages())
                        <div class="nw-pagination-wrap">{{ $notifications->links() }}</div>
                    @endif
                @endif
            </section>
        </div>
    @endfragment
</x-app-layout>
