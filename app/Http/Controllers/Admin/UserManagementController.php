<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\AccountLifecycleService;
use App\Services\ApplicationDocumentFileService;
use App\Support\LiveTables;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function __construct(private AccountLifecycleService $accounts) {}

    public function organizations(Request $request): View|JsonResponse|Response
    {
        return $this->index($request, 'admin.users.index', 'Organizations', UserRole::Organization, showCreate: false, exportRoute: 'admin.users.organizations.export');
    }

    public function administrators(Request $request): View|JsonResponse|Response
    {
        return $this->index($request, 'admin.users.index', 'Administrators', UserRole::Admin, exportRoute: 'admin.users.administrators.export');
    }

    public function applicants(Request $request): View|JsonResponse|Response
    {
        return $this->index($request, 'admin.users.index', 'Applicants', UserRole::Applicant, exportRoute: 'admin.users.applicants.export');
    }

    public function suspended(Request $request): View|JsonResponse|Response
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', 'min:8', Rules\Password::defaults()],
            'role' => ['required', Rule::in($this->getAllowedRoles())],
            'status' => ['required', Rule::in(UserStatus::values())],
        ]);

        if ($data['role'] === UserRole::Organization->value) {
            throw ValidationException::withMessages([
                'role' => 'Create organization access through an approved demo request.',
            ]);
        }

        $this->accounts->createUser($request->user(), $data);

        return LiveTables::mutation($request, 'User created successfully.', back()->with('success', 'User created successfully.'), ['users']);
    }

    public function update(Request $request, User $user): RedirectResponse|JsonResponse
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

        return LiveTables::mutation($request, 'User updated successfully.', back()->with('success', 'User updated successfully.'), ['users']);
    }

    public function suspend(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, UserStatus::Suspended);

        return LiveTables::mutation($request, 'User suspended successfully.', back()->with('success', 'User suspended successfully.'), ['users']);
    }

    public function activate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, UserStatus::Active);

        return LiveTables::mutation($request, 'User activated successfully.', back()->with('success', 'User activated successfully.'), ['users']);
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->accounts->administer($request->user(), $user, $user->role, $user->status, delete: true);

        return LiveTables::mutation($request, 'User deleted successfully.', back()->with('success', 'User deleted successfully.'), ['users']);
    }

    public function sendPasswordReset(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            if ($request->header('X-Live-Action') === '1') {
                return response()->json(['message' => __($status), 'errors' => ['user' => [__($status)]]], 422)
                    ->header('Cache-Control', 'private, no-store');
            }

            return back()->withErrors(['user' => __($status)]);
        }

        return LiveTables::mutation($request, 'Password reset link sent.', back()->with('success', 'Password reset link sent.'), ['users']);
    }

    public function downloadApplicantResume(Request $request, User $user): StreamedResponse
    {
        $this->authorizeAdminApplicant($request, $user);
        $profile = $user->applicantProfile()->firstOrFail();
        $path = trim((string) $profile->resume_path);
        $disk = $this->adminApplicantFileDisk($request, $user, $path, 'candidate-resumes', 'local');

        return $disk->download($path, $this->safeApplicantFilename($profile->resume_original_name, 'resume'), [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function previewApplicantResume(Request $request, User $user): StreamedResponse
    {
        $this->authorizeAdminApplicant($request, $user);
        $profile = $user->applicantProfile()->firstOrFail();
        $path = trim((string) $profile->resume_path);
        $disk = $this->adminApplicantFileDisk($request, $user, $path, 'candidate-resumes', 'local');

        return $this->inlineApplicantDocument($disk, $path, $this->safeApplicantFilename($profile->resume_original_name, 'resume'));
    }

    public function downloadIdentificationDocument(Request $request, User $user): StreamedResponse
    {
        $this->authorizeAdminApplicant($request, $user);
        $document = $user->identificationDocument()->firstOrFail();
        $path = trim((string) $document->file_path);
        $documentDirectory = 'user-identification-documents/'.$user->id;
        $disk = $this->adminApplicantFileDisk(
            $request,
            $user,
            $path,
            $documentDirectory,
            config('filesystems.application_documents_disk', 'local')
        );

        $filename = $this->safeApplicantFilename($document->original_name, 'identity-document');

        return $disk->download($path, $filename, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function previewIdentificationDocument(Request $request, User $user): StreamedResponse
    {
        $this->authorizeAdminApplicant($request, $user);
        $document = $user->identificationDocument()->firstOrFail();
        $path = trim((string) $document->file_path);
        $documentDirectory = 'user-identification-documents/'.$user->id;
        $disk = $this->adminApplicantFileDisk(
            $request,
            $user,
            $path,
            $documentDirectory,
            config('filesystems.application_documents_disk', 'local')
        );

        return $this->inlineApplicantDocument($disk, $path, $this->safeApplicantFilename($document->original_name, 'identity-document'));
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
                'phone_country',
                'phone_dial_code',
                'phone_national_number',
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
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id', $sortDirection);
    }

    private function index(
        Request $request,
        string $view,
        string $title,
        ?UserRole $role = null,
        ?UserStatus $status = null,
        bool $showCreate = true,
        ?string $exportRoute = null,
    ): View|JsonResponse|Response {
        $this->validateFilters($request);
        $sortableColumns = ['first_name', 'last_name', 'email', 'phone', 'created_at'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPageOptions = [10];
        $perPage = 10;
        $search = trim((string) $request->input('search', $request->input('name', '')));

        $users = LiveTables::paginate($this->filteredQuery($request, $role, $status, $sortColumn, $sortDirection, $search), $perPage);

        $applicantUsers = $users->getCollection()->filter(fn (User $user): bool => $user->isApplicant());

        if ($applicantUsers->isNotEmpty()) {
            $applicantUsers->load([
                'applicantProfile.employments',
                'applicantProfile.educations',
                'applicantProfile.skills',
                'applicantProfile.links',
                'applicantProfile.selfIdentification',
                'jobPreferences',
                'identificationDocument',
            ]);

            $applications = ApplicationForm::withoutGlobalScope(OrganizationScope::class)
                ->whereIn('user_id', $applicantUsers->modelKeys())
                ->with([
                    'job' => fn ($query) => $query->withoutGlobalScope(OrganizationScope::class)->select(['id', 'title']),
                    'documents' => fn ($query) => $query->withoutGlobalScope(OrganizationScope::class)->select([
                        'id',
                        'application_form_id',
                        'organization_id',
                        'user_identification_document_id',
                        'document_type',
                        'document_name',
                        'file_path',
                        'original_name',
                        'mime_type',
                        'size',
                        'created_at',
                    ]),
                ])
                ->get(['id', 'user_id', 'job_id', 'reference', 'submitted_at']);

            $documentsByApplicant = [];

            foreach ($applications as $application) {
                foreach ($application->documents as $document) {
                    $document->setAttribute('application_reference', $application->reference);
                    $document->setAttribute('application_job_title', $application->job?->title);
                    $documentsByApplicant[$application->user_id][] = $document;
                }
            }

            foreach ($applicantUsers as $applicant) {
                $applicant->setRelation(
                    'adminApplicantDocuments',
                    collect($this->applicantDocumentItems($applicant, $documentsByApplicant[$applicant->id] ?? []))
                );
            }
        }

        $indexView = view($view, [
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
            'employmentTypes' => Job::employmentTypeOptions(),
            'workplaceLabels' => ['on_site' => __('On-site'), 'hybrid' => __('Hybrid'), 'remote' => __('Remote')],
        ]);

        $response = LiveTables::view($request, $indexView, ['admin-users']);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        return response()->view($response->name(), $response->getData())
            ->header('Cache-Control', 'private, no-store')
            ->header('X-Content-Type-Options', 'nosniff');
    }

    /** @param list<ApplicationDocument> $applicationDocuments
     *  @return list<array{id: string, label: string, filename: string, size: ?string, contexts: list<string>, available: bool, previewable: bool, preview_url: string, download_url: string}>
     */
    private function applicantDocumentItems(User $applicant, array $applicationDocuments): array
    {
        $items = [];
        $indexesByStoredFile = [];
        $append = function (string $diskName, string $path, array $item) use (&$items, &$indexesByStoredFile): void {
            $key = $diskName.'|'.$path;

            if (isset($indexesByStoredFile[$key])) {
                $index = $indexesByStoredFile[$key];
                $items[$index]['contexts'] = array_values(array_unique([
                    ...$items[$index]['contexts'],
                    ...$item['contexts'],
                ]));

                return;
            }

            $indexesByStoredFile[$key] = count($items);
            $items[] = $item;
        };

        $profile = $applicant->applicantProfile;

        if (filled($profile?->resume_path)) {
            $path = trim((string) $profile->resume_path);
            $diskName = 'local';
            $safePath = $this->pathIsInsideDirectory($path, 'candidate-resumes');
            $disk = Storage::disk($diskName);
            $available = $safePath && $disk->exists($path);
            $previewable = $available && $this->detectedInlineMimeType($disk, $path) !== null;

            $append($diskName, $path, [
                'id' => 'profile-resume',
                'label' => __('Profile résumé'),
                'filename' => $this->safeApplicantFilename($profile->resume_original_name, 'resume'),
                'size' => $profile->resume_size ? number_format($profile->resume_size / 1024).' KB' : null,
                'contexts' => [__('Profile')],
                'available' => $available,
                'previewable' => $previewable,
                'preview_url' => route('admin.users.applicant-resume.preview', $applicant),
                'download_url' => route('admin.users.applicant-resume.download', $applicant),
            ]);
        }

        $identity = $applicant->identificationDocument;

        if (filled($identity?->file_path)) {
            $path = trim((string) $identity->file_path);
            $diskName = config('filesystems.application_documents_disk', 'local');
            $directory = 'user-identification-documents/'.$applicant->id;
            $safePath = $this->pathIsInsideDirectory($path, $directory);
            $disk = Storage::disk($diskName);
            $available = $safePath && $disk->exists($path);
            $previewable = $available && $this->detectedInlineMimeType($disk, $path) !== null;

            $append($diskName, $path, [
                'id' => 'identity-document',
                'label' => $identity->document_type?->label() ?? __('Identity document'),
                'filename' => $this->safeApplicantFilename($identity->original_name, 'identity-document'),
                'size' => $identity->size ? number_format($identity->size / 1024).' KB' : null,
                'contexts' => [__('Applicant profile')],
                'available' => $available,
                'previewable' => $previewable,
                'preview_url' => route('admin.users.identification-document.preview', $applicant),
                'download_url' => route('admin.users.identification-document.download', $applicant),
            ]);
        }

        $documentFiles = app(ApplicationDocumentFileService::class);

        foreach ($applicationDocuments as $document) {
            $path = trim((string) $document->file_path);

            if ($path === '') {
                continue;
            }

            $metadata = $documentFiles->metadataFor($document);
            $context = collect([$document->application_job_title, $document->application_reference])
                ->filter()
                ->implode(' · ');

            $append(config('filesystems.application_documents_disk', 'local'), $path, [
                'id' => 'application-document-'.$document->id,
                'label' => $document->displayLabel(),
                'filename' => $this->safeApplicantFilename($document->original_name ?: $document->document_name, 'document'),
                'size' => $document->size ? number_format($document->size / 1024).' KB' : null,
                'contexts' => [$context ?: __('Application document')],
                'available' => $metadata['available'],
                'previewable' => $metadata['available'] && $metadata['previewable'],
                'preview_url' => $document->previewUrl(),
                'download_url' => $document->downloadUrl(),
            ]);
        }

        return $items;
    }

    private function adminApplicantFileDisk(Request $request, User $user, string $path, string $directory, string $diskName): FilesystemAdapter
    {
        $this->authorizeAdminApplicant($request, $user);
        abort_unless($this->pathIsInsideDirectory($path, $directory), 404);

        $disk = Storage::disk($diskName);
        abort_unless($disk->exists($path), 404);

        return $disk;
    }

    private function authorizeAdminApplicant(Request $request, User $user): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
        abort_unless($user->isApplicant(), 404);
    }

    private function safeApplicantFilename(?string $filename, string $fallback): string
    {
        $filename = basename(str_replace('\\', '/', trim((string) $filename)));

        return $filename !== '' ? $filename : $fallback;
    }

    private function pathIsInsideDirectory(string $path, string $directory): bool
    {
        return str_starts_with($path, $directory.'/')
            && dirname($path) === $directory
            && ! str_contains($path, '\\');
    }

    private function inlineApplicantDocument(FilesystemAdapter $disk, string $path, string $filename): StreamedResponse
    {
        $mimeType = $this->detectedInlineMimeType($disk, $path);
        abort_unless($mimeType !== null, 415, 'This file type cannot be previewed. Download it instead.');

        return $disk->response($path, $filename, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ], 'inline');
    }

    private function detectedInlineMimeType(FilesystemAdapter $disk, string $path): ?string
    {
        $stream = $disk->readStream($path);
        if (! is_resource($stream)) {
            return null;
        }

        try {
            $signature = fread($stream, 8);
        } finally {
            fclose($stream);
        }

        $mimeType = match (true) {
            is_string($signature) && str_starts_with($signature, '%PDF-') => 'application/pdf',
            is_string($signature) && str_starts_with($signature, "\x89PNG\r\n\x1a\n") => 'image/png',
            is_string($signature) && str_starts_with($signature, "\xff\xd8\xff") => 'image/jpeg',
            default => null,
        };

        return $mimeType;
    }

    private function export(Request $request, string $filenameSlug, ?UserRole $role, ?UserStatus $status = null): StreamedResponse
    {
        $this->validateFilters($request);
        $search = trim((string) $request->input('search', $request->input('name', '')));
        $users = $this->filteredQuery($request, $role, $status, 'created_at', 'desc', $search)->get();
        $filename = 'marthire-'.$filenameSlug.'-'.now()->toDateString().'.csv';

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

    private function validateFilters(Request $request): void
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:80'],
            'role' => ['nullable', Rule::enum(UserRole::class)],
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'sort' => ['nullable', Rule::in(['first_name', 'last_name', 'email', 'phone', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => ['nullable', 'date_format:Y-m-d'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
    }

    private function getAllowedRolesAsEnums(): array
    {
        return UserRole::cases();
    }
}
