<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteModeratedJobRequest;
use App\Http\Requests\SuspendJobRequest;
use App\Models\Job;
use App\Models\Organization;
use App\Services\JobCountryService;
use App\Services\WorkflowNotificationService;
use App\Services\WorkspaceUpdateBroadcaster;
use App\Support\JobDescription;
use App\Support\LiveTables;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class JobManagementController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        return $this->listing($request, null, 'All Jobs');
    }

    public function open(Request $request): View|JsonResponse
    {
        return $this->listing($request, JobStatus::Open, 'Open Jobs');
    }

    public function closed(Request $request): View|JsonResponse
    {
        return $this->listing($request, JobStatus::Closed, 'Closed Jobs');
    }

    public function suspended(Request $request): View|JsonResponse
    {
        return $this->listing($request, JobStatus::Suspended, 'Suspended Jobs');
    }

    public function show(Request $request, Job $job, JobCountryService $countries): View|JsonResponse
    {
        Gate::authorize('viewModeration', $job);

        $job->load(['employer:id,first_name,last_name,email', 'organization:id,name', 'suspendedBy:id,first_name,last_name'])
            ->loadCount('applications');

        $view = view('admin.jobs.show', [
            'job' => $job,
            'country' => $countries->find($job->country_code),
            'employmentTypeLabel' => $job->employmentTypeLabel(),
            'descriptionHtml' => app(JobDescription::class)->toHtml($job->description),
        ]);

        return LiveTables::view($request, $view, ['admin-job-detail']);
    }

    public function suspend(
        SuspendJobRequest $request,
        Job $job,
        WorkflowNotificationService $notifications,
        WorkspaceUpdateBroadcaster $workspaceUpdates,
    ): RedirectResponse|JsonResponse {
        DB::transaction(function () use ($job, $request, $notifications): void {
            $lockedJob = Job::query()->lockForUpdate()->findOrFail($job->id);
            Gate::authorize('suspend', $lockedJob);

            // Closed jobs are already removed from public discovery. Restricting
            // suspension to open jobs preserves the original state and avoids
            // needing an untracked restore status.
            if ($lockedJob->status !== JobStatus::Open) {
                throw ValidationException::withMessages([
                    'suspension_reason' => 'Only open jobs can be suspended. Closed and already suspended jobs keep their current status.',
                ]);
            }

            $lockedJob->forceFill([
                'status' => JobStatus::Suspended,
                'suspended_at' => now(),
                'suspension_reason' => $request->validated('suspension_reason'),
                'suspended_by' => $request->user()->id,
            ])->save();
            $notifications->organizationJobSuspended($lockedJob);
        });

        $workspaceUpdates->admins(['jobs']);
        $workspaceUpdates->organization(
            $job->organization_id,
            ['jobs', 'applications', 'candidates', 'notifications'],
            'A job posting was suspended by platform administration.',
            'warning',
        );

        $message = 'Job suspended successfully.';

        if ($request->expectsJson() && $request->header('X-Live-Action') !== '1') {
            return response()->json([
                'ok' => true,
                'message' => $message,
                'status' => JobStatus::Suspended->value,
                'redirect' => route('admin.jobs.show', $job),
            ])->header('Cache-Control', 'private, no-store');
        }

        return LiveTables::mutation(
            $request,
            $message,
            back()->with('success', $message),
            ['jobs', 'applications', 'candidates'],
        );
    }

    public function destroy(DeleteModeratedJobRequest $request, Job $job, WorkspaceUpdateBroadcaster $workspaceUpdates): RedirectResponse|JsonResponse
    {
        $organizationId = $job->organization_id;
        $logoPath = DB::transaction(function () use ($job): ?string {
            Organization::whereKey($job->organization_id)->lockForUpdate()->firstOrFail();
            $lockedJob = Job::query()->lockForUpdate()->findOrFail($job->id);
            Gate::authorize('deleteAsModerator', $lockedJob);

            // job_posts.job_id cascades into application records and their
            // documents/history. Never let moderator deletion erase candidates.
            if ($lockedJob->applications()->exists()) {
                throw ValidationException::withMessages([
                    'job' => 'This job has applications and cannot be permanently deleted. Close or suspend it so candidate records remain available.',
                ]);
            }

            $logoPath = $lockedJob->storedLogoPath();
            $lockedJob->delete();

            return $logoPath;
        });

        if ($logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
        }

        $workspaceUpdates->admins(['jobs']);
        $workspaceUpdates->organization(
            $organizationId,
            ['jobs', 'applications', 'candidates'],
            'A job posting was removed by platform administration.',
            'warning',
        );

        LiveTables::signal($request, ['jobs', 'applications', 'candidates']);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Job deleted successfully.',
                'redirect' => route('admin.jobs.index'),
            ])->header('Cache-Control', 'private, no-store');
        }

        return redirect()
            ->route('admin.jobs.index')
            ->with('success', 'Job deleted successfully.');
    }

    private function listing(Request $request, ?JobStatus $statusConstraint, string $title): View|JsonResponse
    {
        $sortableColumns = ['created_at', 'status', 'title', 'company', 'category', 'employer'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = 10;
        $searchInput = $request->query('search', '');
        $search = is_string($searchInput) ? mb_substr(trim($searchInput), 0, 120) : '';
        $categoryInput = $request->query('category', '');
        $category = is_string($categoryInput) ? mb_substr(trim($categoryInput), 0, 255) : '';
        $statusFilter = $this->statusFilter($request, $statusConstraint);

        $query = Job::query()
            ->select('job_posts.*')
            ->with(['employer:id,first_name,last_name,email', 'organization:id,name'])
            ->withCount('applications')
            ->where('status', '<>', JobStatus::Draft->value) // Draft is org-private; admins never moderate it
            ->when($statusConstraint, fn ($query) => $query->status($statusConstraint))
            ->when(! $statusConstraint && $statusFilter, fn ($query) => $query->status($statusFilter))
            ->when($category !== '', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($search !== '', function ($query) use ($search) {
                collect(preg_split('/\s+/', $search) ?: [])
                    ->filter()
                    ->each(function (string $term) use ($query) {
                        $term = '%'.$term.'%';

                        $query->where(function ($query) use ($term) {
                            $query->where('job_posts.title', 'like', $term)
                                ->orWhere('job_posts.company', 'like', $term)
                                ->orWhere('job_posts.category', 'like', $term)
                                ->orWhereHas('employer', function ($query) use ($term) {
                                    $query->where('first_name', 'like', $term)
                                        ->orWhere('last_name', 'like', $term)
                                        ->orWhere('email', 'like', $term);
                                });
                        });
                    });
            });

        if ($sortColumn === 'employer') {
            $query
                ->leftJoin('users as employers', 'employers.id', '=', 'job_posts.employer_id')
                ->orderBy('employers.first_name', $sortDirection)
                ->orderBy('employers.last_name', $sortDirection)
                ->orderBy('job_posts.created_at', 'desc');
        } else {
            $query->orderBy('job_posts.'.$sortColumn, $sortDirection);
        }

        $jobs = LiveTables::paginate($query, $perPage);

        return LiveTables::view($request, view('admin.jobs.index', [
            'jobs' => $jobs,
            'title' => $title,
            'statusConstraint' => $statusConstraint,
            'statuses' => JobStatus::moderatableCases(),
            'categories' => $this->categories(),
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'filterValues' => [
                'search' => $search,
                'status' => $statusFilter?->value,
                'category' => $category,
                'per_page' => $perPage,
            ],
        ]), ['admin-jobs']);
    }

    private function statusFilter(Request $request, ?JobStatus $statusConstraint): ?JobStatus
    {
        if ($statusConstraint || ! $request->filled('status')) {
            return null;
        }

        $moderatableValues = array_map(fn (JobStatus $status): string => $status->value, JobStatus::moderatableCases());

        if (! in_array($request->input('status'), $moderatableValues, true)) {
            return null;
        }

        return JobStatus::from($request->input('status'));
    }

    /**
     * @return list<string>
     */
    private function categories(): array
    {
        return Job::query()
            ->where('status', '<>', JobStatus::Draft->value)
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
