<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteModeratedJobRequest;
use App\Http\Requests\SuspendJobRequest;
use App\Models\Job;
use App\Models\Organization;
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
    public function index(Request $request): View
    {
        return $this->listing($request, null, 'All Jobs');
    }

    public function open(Request $request): View
    {
        return $this->listing($request, JobStatus::Open, 'Open Jobs');
    }

    public function closed(Request $request): View
    {
        return $this->listing($request, JobStatus::Closed, 'Closed Jobs');
    }

    public function suspended(Request $request): View
    {
        return $this->listing($request, JobStatus::Suspended, 'Suspended Jobs');
    }

    public function show(Job $job): View
    {
        return view('admin.jobs.show', [
            'job' => $job->load(['employer', 'suspendedBy:id,first_name,last_name'])->loadCount('applications'),
        ]);
    }

    public function suspend(SuspendJobRequest $request, Job $job): RedirectResponse|JsonResponse
    {
        $updated = Job::query()->whereKey($job->id)->where('status', '<>', JobStatus::Suspended->value)->update([
            'status' => JobStatus::Suspended,
            'suspended_at' => now(),
            'suspension_reason' => $request->validated('suspension_reason'),
            'suspended_by' => $request->user()->id,
        ]);

        if ($updated === 0) {
            throw ValidationException::withMessages(['suspension_reason' => 'This job has already been suspended.']);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Job suspended.',
                'status' => JobStatus::Suspended->value,
                'redirect' => route('admin.jobs.show', $job),
            ]);
        }

        return back()->with('success', 'Job suspended.');
    }

    public function destroy(DeleteModeratedJobRequest $request, Job $job): RedirectResponse|JsonResponse
    {
        $logoPath = $job->storedLogoPath();
        DB::transaction(function () use ($job): void {
            Organization::whereKey($job->organization_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('deleteAsModerator', $job);

            if ($job->applications()->whereHas('candidateMessages')->exists()) {
                throw ValidationException::withMessages(['job' => 'Jobs with candidate email history must be closed instead of deleted.']);
            }

            $job->delete();
        });

        if ($logoPath !== null) {
            Storage::disk('public')->delete($logoPath);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Job deleted successfully.',
                'redirect' => route('admin.jobs.index'),
            ]);
        }

        return redirect()
            ->route('admin.jobs.index')
            ->with('success', 'Job deleted successfully.');
    }

    private function listing(Request $request, ?JobStatus $statusConstraint, string $title): View
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
            ->with(['employer:id,first_name,last_name,email'])
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

        $jobs = $query
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.jobs.index', [
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
        ]);
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
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }
}
