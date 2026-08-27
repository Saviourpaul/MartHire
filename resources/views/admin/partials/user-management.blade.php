@php
    $pageTitle = 'All ' . $title;
    $statusClasses = [
        'active' => 'bg-success-light',
        'suspended' => 'bg-danger-light',
    ];
    $filterValues = $filterValues ?? [
        'search' => request('search', request('name')),
        'email' => request('email'),
        'phone' => request('phone'),
        'role' => request('role'),
        'status' => request('status'),
        'created_from' => request('created_from'),
        'created_to' => request('created_to'),
        'per_page' => request('per_page', 15),
    ];
    $perPageOptions = $perPageOptions ?? [15, 25, 50, 100];

    $sortIcon = fn($column) => $sortColumn === $column ? ($sortDirection === 'asc' ? '↑' : '↓') : '↕';

    $sortUrl = fn($column) => request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $sortColumn === $column && $sortDirection === 'asc' ? 'desc' : 'asc',
    ]);
@endphp

<div class="page-header subscribe-head">
    <div class="row align-items-center g-3">
        <div class="col">
            <h3 class="page-title">{{ $pageTitle }}</h3>
            <p class="mb-0 text-muted">
                {{ number_format($users->total()) }} {{ Str::plural('user', $users->total()) }}
            </p>
        </div>
        @if ($showCreate)
            <!-- class="col-auto">
                <button class="btn add-user" type="button" data-bs-toggle="modal" data-bs-target="#create-user">
                    <i class="fas fa-plus"></i> Add User
                </button>
            </div-->
        @endif
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="subscribe-employe users-list">
            <ul>
                <li class="active">{{ $pageTitle }}</li>
            </ul>
        </div>

        <div class="card filter-card mb-4" id="filter_inputs">
            <div class="card-body">
                <form action="{{ url()->current() }}" method="GET">
                    <input type="hidden" name="sort" value="{{ $sortColumn }}">
                    <input type="hidden" name="direction" value="{{ $sortDirection }}">

                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-6">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="feather-search"></i></span>
                                <input class="form-control" name="search" type="search"
                                    placeholder="Name, username, email, phone..." value="{{ $filterValues['search'] }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ $filterValues['email'] }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                value="{{ $filterValues['phone'] }}">
                        </div>
                        @unless ($roleConstraint)
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Role</label>
                                <select class="form-control form-select" name="role">
                                    <option value="">All roles</option>
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->value }}" @selected($filterValues['role'] === $role->value)>
                                            {{ $role->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endunless
                        @unless ($statusConstraint)
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label">Status</label>
                                <select class="form-control form-select" name="status">
                                    <option value="">All statuses</option>
                                    @foreach ($statuses as $status)
                                        <option value="{{ $status->value }}" @selected($filterValues['status'] === $status->value)>
                                            {{ $status->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endunless
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Joined From</label>
                            <input class="form-control" name="created_from" type="date"
                                value="{{ $filterValues['created_from'] }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Joined To</label>
                            <input class="form-control" name="created_to" type="date"
                                value="{{ $filterValues['created_to'] }}">
                        </div>
                        <div class="col-lg-2 col-md-6">
                            <label class="form-label">Rows</label>
                            <select class="form-control form-select" name="per_page">
                                @foreach ($perPageOptions as $option)
                                    <option value="{{ $option }}" @selected((int) $filterValues['per_page'] === $option)>
                                        {{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-6 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" type="submit">
                                <i class="feather-filter me-1"></i> Filter
                            </button>
                            <a href="{{ url()->current() }}" class="btn btn-outline-secondary flex-fill">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h5 class="mb-1">{{ $pageTitle }}</h5>
                        @if ($users->count())
                            <span class="text-muted">
                                Showing
                                {{ number_format($users->firstItem()) }}-{{ number_format($users->lastItem()) }}
                                of {{ number_format($users->total()) }}
                            </span>
                        @else
                            <span class="text-muted">No users match the current filters.</span>
                        @endif
                    </div>
                    @if ($roleConstraint || $statusConstraint)
                        <div class="d-flex flex-wrap gap-2">
                            @if ($roleConstraint)
                                <span class="badge bg-light text-dark">{{ $roleConstraint->label() }}</span>
                            @endif
                            @if ($statusConstraint)
                                <span class="badge bg-light text-dark">{{ $statusConstraint->label() }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 user-table">
                        <thead>
                            <tr>
                                <th>
                                    <a href="{{ $sortUrl('created_at') }}" class="text-decoration-none">
                                        #<span class="ms-1">{{ $sortIcon('created_at') }}</span>
                                    </a>
                                </th>
                                <th>
                                    <a href="{{ $sortUrl('first_name') }}" class="text-decoration-none">
                                        User<span class="ms-1">{{ $sortIcon('first_name') }}</span>
                                    </a>
                                </th>
                                <th class="d-none d-md-table-cell">
                                    <a href="{{ $sortUrl('email') }}" class="text-decoration-none">
                                        Email<span class="ms-1">{{ $sortIcon('email') }}</span>
                                    </a>
                                </th>
                                <th class="d-none d-lg-table-cell">
                                    <a href="{{ $sortUrl('phone') }}" class="text-decoration-none">
                                        Phone<span class="ms-1">{{ $sortIcon('phone') }}</span>
                                    </a>
                                </th>
                                <th class="d-none d-xl-table-cell">Role</th>
                                <th>Status</th>
                                <th class="d-none d-xxl-table-cell">
                                    <a href="{{ $sortUrl('created_at') }}" class="text-decoration-none">
                                        Joined<span class="ms-1">{{ $sortIcon('created_at') }}</span>
                                    </a>
                                </th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $users->firstItem() + $loop->index }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img class="avatar-img rounded-circle me-2"
                                                src="{{ $user->profileImageUrl() }}" alt="{{ $user->first_name }}"
                                                width="40" height="40" style="object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0">{{ $user->first_name }} {{ $user->last_name }}
                                                </h6>
                                                <small class="text-muted">{{ '@' . $user->username }}</small>
                                                <small
                                                    class="text-muted d-md-none d-block">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="d-none d-md-table-cell">{{ $user->email }}</td>
                                    <td class="d-none d-lg-table-cell">{{ $user->phone ?: 'Not provided' }}</td>
                                    <td class="d-none d-xl-table-cell">{{ $user->role->label() }}</td>
                                    <td>
                                        <span
                                            class="badge {{ $statusClasses[$user->status->value] ?? 'bg-secondary' }}">
                                            {{ $user->status->label() }}
                                        </span>
                                    </td>
                                    <td class="d-none d-xxl-table-cell">{{ $user->created_at?->format('d M Y') }}</td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-light btn-sm dropdown-toggle" type="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                Actions
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                @if ($user->isApplicant() && auth()->user()->hasRole('admin', 'employer'))
                                                    <li>
                                                        <a class="dropdown-item"
                                                            href="{{ route('applicants.profile.show', $user) }}">
                                                            <i data-feather="eye" class="me-2"></i> View Profile
                                                        </a>
                                                    </li>
                                                @endif
                                                <li>
                                                    <button class="dropdown-item" type="button"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#edit-user-{{ $user->id }}">
                                                        <i data-feather="edit" class="me-2"></i> Edit
                                                    </button>
                                                </li>
                                                <li>
                                                    <form action="{{ route('admin.users.password-reset', $user) }}"
                                                        method="POST" class="d-grid" data-confirm
                                                        data-confirm-title="Send password reset?"
                                                        data-confirm-text="A reset link will be emailed to this user."
                                                        data-confirm-button="Send Link">
                                                        @csrf
                                                        <button class="dropdown-item" type="submit">
                                                            <i data-feather="key" class="me-2"></i> Reset Password
                                                        </button>
                                                    </form>
                                                </li>
                                                @if (!$user->is(auth()->user()) && !$user->isSuspended())
                                                    <li>
                                                        <form action="{{ route('admin.users.suspend', $user) }}"
                                                            method="POST" class="d-grid" data-confirm
                                                            data-confirm-title="Suspend user?"
                                                            data-confirm-text="This user will lose access until reactivated."
                                                            data-confirm-button="Suspend">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="dropdown-item text-danger" type="submit">
                                                                <i data-feather="slash" class="me-2"></i> Suspend
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                                @if ($user->isSuspended())
                                                    <li>
                                                        <form action="{{ route('admin.users.activate', $user) }}"
                                                            method="POST" class="d-grid" data-confirm
                                                            data-confirm-title="Activate user?"
                                                            data-confirm-text="This user will be allowed to access the system."
                                                            data-confirm-button="Activate">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button class="dropdown-item text-success" type="submit">
                                                                <i data-feather="check-circle" class="me-2"></i>
                                                                Activate
                                                            </button>
                                                        </form>
                                                    </li>
                                                @endif
                                                @if (!$user->is(auth()->user()))
                                                    <li>
                                                        <button class="dropdown-item text-danger mb-0" type="button"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#delete-user-{{ $user->id }}">
                                                            <i data-feather="trash-2" class="me-2"></i> Delete
                                                        </button>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">No users found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="mt-4">
                        {{ $users->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($showCreate)
    <div class="modal fade custom-modal" id="create-user" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header flex-wrap">
                    <h4 class="modal-title">Add User</h4>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.users.store') }}" method="POST" data-confirm
                        data-confirm-title="Create user?"
                        data-confirm-text="A new user account will be created with these details."
                        data-confirm-button="Create User">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <div class="position-relative">
                                <input type="password" name="password" class="form-control" required minlength="8"
                                    placeholder="Use at least 8 characters">
                                <button type="button"
                                    class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-2"
                                    data-password-toggle="password" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="position-relative">
                                <input type="password" name="password_confirmation" class="form-control" required
                                    minlength="8" placeholder="Re-enter password">
                                <button type="button"
                                    class="btn btn-link p-0 position-absolute top-50 end-0 translate-middle-y me-2"
                                    data-password-toggle="password_confirmation" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control form-select" name="role" required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($defaultRole === $role)>
                                        {{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select class="form-control form-select" name="status" required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($defaultStatus === $status)>
                                        {{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-block">Create User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@foreach ($users as $user)
    <div class="modal fade custom-modal" id="edit-user-{{ $user->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header flex-wrap">
                    <h4 class="modal-title">Edit User</h4>
                    <button type="button" class="close" data-bs-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('admin.users.update', $user) }}" method="POST" data-confirm
                        data-confirm-title="Update user?"
                        data-confirm-text="This user's role and status will be updated."
                        data-confirm-button="Save Changes">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control"
                                        value="{{ $user->first_name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control"
                                        value="{{ $user->last_name }}" readonly>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control"
                                value="{{ $user->username }}" readonly>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="{{ $user->email }}"
                                readonly>
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            @if ($user->is(auth()->user()))
                                <input type="hidden" name="role" value="{{ $user->role->value }}">
                            @endif
                            <select class="form-control form-select" name="role" @disabled($user->is(auth()->user()))
                                required>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}" @selected($user->role === $role)>
                                        {{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            @if ($user->is(auth()->user()))
                                <input type="hidden" name="status" value="{{ $user->status->value }}">
                            @endif
                            <select class="form-control form-select" name="status" @disabled($user->is(auth()->user()))
                                required>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status->value }}" @selected($user->status === $status)>
                                        {{ $status->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (!$user->is(auth()->user()))
        <div class="modal custom-modal fade" id="delete-user-{{ $user->id }}" tabindex="-1"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="form-header">
                            <h3>Delete</h3>
                            <p>Are you sure you want to delete this user?</p>
                        </div>
                        <div class="modal-btn delete-action">
                            <div class="row">
                                <div class="col-6">
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                        data-confirm data-confirm-title="Delete user?"
                                        data-confirm-text="This user account will be permanently removed."
                                        data-confirm-button="Delete User">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-primary continue-btn w-100">Delete</button>
                                    </form>
                                </div>
                                <div class="col-6">
                                    <button type="button" data-bs-dismiss="modal"
                                        class="btn btn-primary cancel-btn w-100">Cancel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach
