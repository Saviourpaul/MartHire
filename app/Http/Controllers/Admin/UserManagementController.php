<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class UserManagementController extends Controller
{
    public function employers(Request $request): View
    {
        return $this->index($request, 'admin.Employers', 'Employers', UserRole::Employer);
    }

    public function administrators(Request $request): View
    {
        return $this->index($request, 'admin.administrators', 'Administrators', UserRole::Admin);
    }

    public function applicants(Request $request): View
    {
        return $this->index($request, 'admin.applicants', 'Applicants', UserRole::Applicant);
    }

    public function suspended(Request $request): View
    {
        return $this->index(
            request: $request,
            view: 'admin.suspended-accounts',
            title: 'Suspended Accounts',
            status: UserStatus::Suspended,
            showCreate: false,
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
            'role' => ['required', Rule::in($this->getAllowedRoles())],
            'status' => ['required', Rule::in(UserStatus::values())],
        ]);

        $user = new User([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::from($data['role']),
        ]);

        $this->setStatus($user, UserStatus::from($data['status']));
        $user->save();

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'role' => ['required', Rule::in($this->getAllowedRoles())],
            'status' => ['required', Rule::in(UserStatus::values())],
        ]);

        $role = UserRole::from($data['role']);
        $status = UserStatus::from($data['status']);

        if ($error = $this->protectedAdminError($request->user(), $user, $role, $status)) {
            return back()->withErrors(['user' => $error]);
        }

        $user->fill([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'role' => $role,
        ]);

        $this->setStatus($user, $status);
        $user->save();

        return back()->with('success', 'User updated successfully.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        if ($error = $this->protectedAdminError($request->user(), $user, $user->role, UserStatus::Suspended)) {
            return back()->withErrors(['user' => $error]);
        }

        $user->suspend();

        return back()->with('success', 'User suspended successfully.');
    }

    public function activate(User $user): RedirectResponse
    {
        $user->activate();

        return back()->with('success', 'User activated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot delete your  administrator account.']);
        }

        if ($this->wouldRemoveLastActiveAdmin($user, $user->role, UserStatus::Suspended)) {
            return back()->withErrors(['user' => 'You cannot remove the last active administrator.']);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }

    public function sendPasswordReset(User $user): RedirectResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['user' => __($status)]);
        }

        return back()->with('success', 'Password reset link sent.');
    }

    private function index(
        Request $request,
        string $view,
        string $title,
        ?UserRole $role = null,
        ?UserStatus $status = null,
        bool $showCreate = true,
    ): View {
        $sortableColumns = ['first_name', 'last_name',  'email', 'phone', 'created_at'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPageOptions = [10];
        $perPage = 10;
        $search = trim((string) $request->input('search', $request->input('name', '')));

        $users = User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'role',
                'status',
                'phone',
                'profile_image_path',
                'created_at',
                'approved_at',
                'suspended_at',
            ])
            ->when($role, fn ($query) => $query->role($role))
            ->when($status, fn ($query) => $query->status($status))
            ->when($search !== '', function ($query) use ($search) {
                collect(preg_split('/\s+/', $search) ?: [])
                    ->filter()
                    ->each(function (string $term) use ($query) {
                        $term = '%'.$term.'%';

                        $query->where(function ($query) use ($term) {
                            $query->where('first_name', 'like', $term)
                                ->orWhere('last_name', 'like', $term)
                                ->orWhere('email', 'like', $term)
                                ->orWhere('phone', 'like', $term)
                                ->orWhere('local_government_area', 'like', $term)
                                ->orWhere('state_of_origin', 'like', $term)
                                ->orWhere('nationality', 'like', $term);
                        });
                    });
            })
            ->when($request->filled('created_from'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->input('created_from'));
            })
            ->when($request->filled('created_to'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->input('created_to'));
            })
            ->when($request->filled('email'), function ($query) use ($request) {
                $query->where('email', 'like', '%'.$request->string('email')->trim().'%');
            })
            ->when($request->filled('phone'), function ($query) use ($request) {
                $query->where('phone', 'like', '%'.$request->string('phone')->trim().'%');
            })
            ->when(! $role && $request->filled('role'), function ($query) use ($request) {
                if (in_array($request->input('role'), UserRole::values(), true)) {
                    $query->role($request->input('role'));
                }

            })
            ->when(! $status && $request->filled('status'), function ($query) use ($request) {
                if (in_array($request->input('status'), UserStatus::values(), true)) {
                    $query->status($request->input('status'));
                }
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();

        return view($view, [
            'users' => $users,
            'title' => $title,
            'roleConstraint' => $role,
            'statusConstraint' => $status,
            'showCreate' => $showCreate,
            'defaultRole' => $role ?? UserRole::Employer,
            'defaultStatus' => $status === UserStatus::Suspended ? UserStatus::Suspended : UserStatus::Active,
            'roles' => $this->getAllowedRolesAsEnums(),
            'statuses' => UserStatus::cases(),
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'perPageOptions' => $perPageOptions,
            'filterValues' => [
                'search' => $search,
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'role' => $request->input('role'),
                'status' => $request->input('status'),
                'created_from' => $request->input('created_from'),
                'created_to' => $request->input('created_to'),
                'per_page' => $perPage,
            ],
        ]);
    }

    private function setStatus(User $user, UserStatus $status): void
    {
        $user->status = $status;

        if ($status === UserStatus::Active) {
            $user->approved_at ??= now();
            $user->suspended_at = null;

            return;
        }

        if ($status === UserStatus::Suspended) {
            $user->suspended_at ??= now();
        }
    }

    private function protectedAdminError(User $actingUser, User $targetUser, UserRole $targetRole, UserStatus $targetStatus): ?string
    {
        if ($targetUser->is($actingUser) && ($targetRole !== $targetUser->role || $targetStatus !== $targetUser->status)) {
            return 'You cannot suspend or demote your own administrator account.';
        }

        if ($this->wouldRemoveLastActiveAdmin($targetUser, $targetRole, $targetStatus)) {
            return 'You cannot remove the last active administrator.';
        }

        return null;
    }

    private function wouldRemoveLastActiveAdmin(User $targetUser, UserRole $targetRole, UserStatus $targetStatus): bool
    {
        if (! $targetUser->isAdmin() || ! $targetUser->isActive()) {
            return false;
        }

        if ($targetRole === UserRole::Admin && $targetStatus === UserStatus::Active) {
            return false;
        }

        return User::query()
            ->role(UserRole::Admin)
            ->active()
            ->count() <= 1;
    }

    private function getAllowedRoles(): array
    {
        return UserRole::values();
    }

    private function getAllowedRolesAsEnums(): array
    {
        return UserRole::cases();
    }
}
