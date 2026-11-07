<?php

namespace App\Http\Controllers;

use App\Services\NotificationInboxService;
use App\Services\UserPreferencesService;
use App\Support\LiveTables;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private NotificationInboxService $inbox) {}

    public function index(Request $request): View|JsonResponse
    {
        $notifications = $this->inbox->query($request->user())->latest()->paginate(15);

        return LiveTables::view($request, view('notifications.index', [
            'notifications' => $notifications,
            'displayTimezone' => app(UserPreferencesService::class)->displayTimezone($request->user()),
            'notificationItems' => $this->inbox->items($notifications->getCollection(), $request->user()),
            'unreadNotificationCount' => $this->inbox->unreadCount($request->user()),
        ]), ['notification-inbox']);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json(['count' => $this->inbox->unreadCount($request->user())])
            ->header('Cache-Control', 'private, no-store');
    }

    public function preview(Request $request): View|JsonResponse|RedirectResponse
    {
        // This endpoint serves the header dropdown's live fragment only. If a
        // user opens its URL directly, send them to the complete inbox page.
        if (! LiveTables::requested($request)) {
            return redirect()->route('notifications.index');
        }

        return LiveTables::view($request, view('notifications.dropdown-content', $this->inbox->summary($request->user())), ['notification-dropdown']);
    }

    public function read(Request $request, string $notification): RedirectResponse|JsonResponse
    {
        $this->inbox->query($request->user())->whereKey($notification)->firstOrFail()->markAsRead();

        return LiveTables::mutation($request, 'Notification marked as read.', back()->with('success', 'Notification marked as read.'), ['notifications']);
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $this->inbox->query($request->user())->whereNull('read_at')->update(['read_at' => now()]);

        return LiveTables::mutation($request, 'Visible notifications marked as read.', back()->with('success', 'Visible notifications marked as read.'), ['notifications']);
    }
}
