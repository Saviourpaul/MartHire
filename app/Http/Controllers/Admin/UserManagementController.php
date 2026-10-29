<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function __construct(private AccountLifecycleService $accounts) {}

    public function organizations(Request $request): View
    {
        return $this->index($request, 'admin.users.index', 'Organizations', UserRole::Organization, exportRoute: 'admin.users.organizations.export');
    }

    public function administrators(Request $request): View
    {
        return $this->index($request, 'admin.users.index', 'Administrators', UserRole::Admin, exportRoute: 'admin.users.administrators.export');
    }

    public function applicants(Request $request): View
    {
        return $this->index($request, 'admin.users.index', 'Applicants', UserRole::Applicant, exportRoute: 'admin.users.applicants.export');
    }

    public function suspended(Request $request): View
    {
        return $this->index(
            request: $request,
            view: 'admin.users.index',
            title: 'Suspended Accounts',
            status: UserStatus::Suspended,
            showCreate: false,
            exportRoute: 'admin.users.suspended.export',
        );
    }

    public function exportOrganizations(Request $request): StreamedResponse
    {
        return $this->export($request, 'organizations', UserRole::Organization);
    }

    public function exportAdministrators(Request $request): StreamedResponse
    {
        return $this->export($request, 'administrators', UserRole::Admin);
    }

    public function exportApplicants(Request $request): StreamedResponse
    {
        return $this->export($request, 'applicants', UserRole::Applicant);
    }

    public function exportSuspended(Request $request): StreamedResponse
    {
        return $this->export($request, 'suspended-accounts', null, UserStatus::Suspended);
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

        $this->accounts->createUser($request->user(), $data);

        return back()->with('success', 'User created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            // 'first_name' => ['required', 'string', 'max:255'],
            // 'last_name' => ['required', 'string', 'max:255'],
            // 'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'role' => ['required', Rule::in($this->getAllowedRoles())],
            'status' => ['required', Rule::in(UserStatus::values())],
        ]);

        $role = UserRole::from($data['role']);
        $status = UserStatus::from($data['status']);

        $this->accounts->administer($request->user(), $user, $role, $status);

        return back()->with('success', 'User updated successfully.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, UserStatus::Suspended);

        return back()->with('success', 'User suspended successfully.');
    }

    public function activate(Request $request, User $user): RedirectResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, UserStatus::Active);

        return back()->with('success', 'User activated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, $user->status, delete: true);

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

    /**
     * The same filtered-and-sorted query behind both the table view (paginated)
     * and the CSV export (streamed in full) — the filters stay in one place.
     *
     * @return Builder<User>
     */
    private function filteredQuery(Request $request, ?UserRole $role, ?UserStatus $status, string $sortColumn, string $sortDirection, string $search): Builder
    {
        return User::query()
            ->select([
                'id',
                'first_name',
                'last_name',
                'email',
                'role',
                'status',
                'phone',
                'date_of_birth',
                'address',
                'profile_image_path',
                'country_code',
                'country',
                'state',
                'city',
                'nationality',
                'state_of_origin',
                'local_government_area',
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
                                ->orWhere('country', 'like', $term)
                                ->orWhere('state', 'like', $term)
                                ->orWhere('city', 'like', $term)
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
            ->orderBy($sortColumn, $sortDirection);
    }

    private function index(
        Request $request,
        string $view,
        string $title,
        ?UserRole $role = null,
        ?UserStatus $status = null,
        bool $showCreate = true,
        ?string $exportRoute = null,
    ): View {
        $sortableColumns = ['first_name', 'last_name', 'email', 'phone', 'created_at'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPageOptions = [10];
        $perPage = 10;
        $search = trim((string) $request->input('search', $request->input('name', '')));

        $users = $this->filteredQuery($request, $role, $status, $sortColumn, $sortDirection, $search)
            ->paginate($perPage)
            ->withQueryString();

        return view($view, [
            'users' => $users,
            'title' => $title,
            'roleConstraint' => $role,
            'statusConstraint' => $status,
            'showCreate' => $showCreate,
            'exportRoute' => $exportRoute,
            'defaultRole' => $role ?? UserRole::Organization,
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

    private function export(Request $request, string $filenameSlug, ?UserRole $role, ?UserStatus $status = null): StreamedResponse
    {
        $search = trim((string) $request->input('search', $request->input('name', '')));
        $users = $this->filteredQuery($request, $role, $status, 'created_at', 'desc', $search)->get();
        $filename = 'nearbyworks-'.$filenameSlug.'-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($users): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Email', 'Phone', 'Role', 'Status', 'Joined']);

            foreach ($users as $user) {
                fputcsv($handle, [
                    trim($user->first_name.' '.$user->last_name),
                    $user->email,
                    $user->phone ?? '',
                    $user->role->label(),
                    $user->status->label(),
                    $user->created_at->toDateString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
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
