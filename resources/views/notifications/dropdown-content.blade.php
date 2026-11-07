@fragment('notification-dropdown')
    <div class="nw-notification-dropdown__content" data-live-table="notification-dropdown" data-live-topics="notifications" data-live-pages="" data-live-interval="0" data-live-url="{{ route('notifications.preview') }}">
        <header class="nw-notification-dropdown__header">
            <div class="nw-notification-dropdown__title">
                <h2>{{ __('Notifications') }}</h2>
                <span class="nw-notification-dropdown__count nw-notification-count" data-notification-count
                    data-notification-count-value="{{ $unreadNotificationCount }}"
                    aria-label="{{ trans_choice(':count unread notification|:count unread notifications', $unreadNotificationCount, ['count' => $unreadNotificationCount]) }}"
                    @if ($unreadNotificationCount < 1) hidden @endif>{{ $unreadNotificationCount > 99 ? '99+' : ($unreadNotificationCount ?: '') }}</span>
            </div>

            <div class="nw-notification-dropdown__actions">
                @if ($unreadNotificationCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button class="nw-notification-dropdown__mark-all" type="submit">
                            <x-icon name="check" /> {{ __('Mark all read') }}
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <div class="nw-notification-dropdown__list" role="list" aria-label="{{ __('Recent notifications') }}">
            @forelse ($recentNotifications as $notification)
                @php($notificationData = $notification->data)
                <article class="nw-notification-dropdown__item{{ $notification->read_at ? '' : ' is-unread' }}" role="listitem">
                    <a class="nw-notification-dropdown__link" href="{{ $notificationData['action_url'] ?? route('notifications.index') }}">
                        <span class="nw-notification-dropdown__icon" aria-hidden="true"><x-icon name="bell" /></span>
                        <span class="nw-notification-dropdown__copy">
                            <strong>{{ $notificationData['title'] ?? __('Notification') }}</strong>
                            <span>{{ $notificationData['message'] ?? '' }}</span>
                            <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                        </span>
                    </a>
                    @if (! $notification->read_at)
                        <form class="nw-notification-dropdown__read-form" method="POST" action="{{ route('notifications.read', $notification->id) }}">
                            @csrf
                            @method('PATCH')
                            <button class="nw-notification-dropdown__read" type="submit" title="{{ __('Mark as read') }}" aria-label="{{ __('Mark :title as read', ['title' => $notificationData['title'] ?? __('notification')]) }}">
                                <x-icon name="check" />
                            </button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="nw-notification-dropdown__empty">
                    <span class="nw-notification-dropdown__empty-icon" aria-hidden="true"><x-icon name="bell" /></span>
                    <strong>{{ __('You’re all caught up') }}</strong>
                    <span>{{ __('New updates will appear here.') }}</span>
                </div>
            @endforelse
        </div>

        <footer class="nw-notification-dropdown__footer">
            <a href="{{ route('notifications.index') }}">{{ __('View all notifications') }}</a>
        </footer>
    </div>
@endfragment
