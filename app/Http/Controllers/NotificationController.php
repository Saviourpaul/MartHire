<?php

namespace App\Http\Controllers;

use App\Services\NotificationInboxService;
use App\Services\UserPreferencesService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private NotificationInboxService $inbox) {}

    public function index(Request $request): View
    {
        $notifications = $this->inbox->query($request->user())->latest()->paginate(15);

        return view('notifications.index', [
            'notifications' => $notifications,
            'displayTimezone' => app(UserPreferencesService::class)->displayTimezone($request->user()),
            'notificationItems' => $this->inbox->items($notifications->getCollection(), $request->user()),
        ]);
    }

    public function read(Request $request, string $notification): RedirectResponse
    {
        $this->inbox->query($request->user())->whereKey($notification)->firstOrFail()->markAsRead();

        return back()->with('success', 'Notification marked as read.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $this->inbox->query($request->user())->whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'Visible notifications marked as read.');
    }
}
