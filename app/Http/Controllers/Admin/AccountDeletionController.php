<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionLifecycle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AccountDeletionController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $allowedStatuses = ['scheduled', 'completed', 'cancelled'];
        $status = (string) $request->query('status', '');

        $deletions = AccountDeletionLifecycle::query()
            ->with(['user' => fn ($query) => $query
                ->withTrashed()
                ->select(['id', 'first_name', 'last_name', 'email', 'role'])])
            ->whereIn('status', $allowedStatuses)
            ->when(in_array($status, $allowedStatuses, true), fn ($query) => $query->where('status', $status))
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_deletion_at')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();
        $softDeletedUsers = User::onlyTrashed()
            ->select(['id', 'first_name', 'last_name', 'email', 'role', 'deleted_at'])
            ->orderByDesc('deleted_at')
            ->paginate(25, ['*'], 'soft_page')
            ->withQueryString();

        return response()->view('admin.users.deletions.index', [
            'deletions' => $deletions,
            'softDeletedUsers' => $softDeletedUsers,
            'statusFilter' => in_array($status, $allowedStatuses, true) ? $status : '',
            'statuses' => $allowedStatuses,
        ])->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
