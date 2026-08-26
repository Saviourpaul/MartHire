<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobManagementController extends Controller
{
    public function index(Request $request): View
    {
        return $this->listing($request, null, 'All Jobs');
    }

    public function approved(Request $request): View
    {
        return $this->listing($request, JobStatus::Approved, 'Approved Jobs');
    }

    public function pending(Request $request): View
    {
        return $this->listing($request, JobStatus::Pending, 'Pending Jobs');
    }

    public function rejected(Request $request): View
    {
        return $this->listing($request, JobStatus::Rejected, 'Rejected Jobs');
    }

    public function show(Job $job): View
    {
        return view('admin.jobs.show', [
            'job' => $job->load('employer')->loadCount('applications'),
            'statuses' => JobStatus::cases(),
        ]);
    }

    public function review(Request $request, Job $job): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(JobStatus::values())],
        ]);

        $status = JobStatus::from($data['status']);
        $job->status = $status;
        $job->save();

        return back()->with('success', 'Job marked as '.$status->label().'.');
    }

    private function listing(Request $request, ?JobStatus $statusConstraint, string $title): View
    {
        $sortableColumns = ['created_at', 'status', 'title', 'company', 'category', 'employer'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $perPageOptions = [15, 25, 50, 100];
        $perPage = (int) $request->input('per_page', 15);
        $perPage = in_array($perPage, $perPageOptions, true) ? $perPage : 15;
        $search = trim((string) $request->input('search', ''));
        $statusFilter = $this->statusFilter($request, $statusConstraint);

        $query = Job::query()
            ->select('job_posts.*')
            ->with(['employer:id,first_name,last_name,email,profile_image_path'])
            ->withCount('applications')
            ->when($statusConstraint, fn ($query) => $query->status($statusConstraint))
            ->when(! $statusConstraint && $statusFilter, fn ($query) => $query->status($statusFilter))
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->where('category', $request->input('category'));
            })
            ->when($request->filled('submitted_from'), function ($query) use ($request) {
                $query->whereDate('job_posts.created_at', '>=', $request->input('submitted_from'));
            })
            ->when($request->filled('submitted_to'), function ($query) use ($request) {
                $query->whereDate('job_posts.created_at', '<=', $request->input('submitted_to'));
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
            'statuses' => JobStatus::cases(),
            'statusCounts' => $this->statusCounts(),
            'categories' => $this->categories(),
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
            'perPageOptions' => $perPageOptions,
            'filterValues' => [
                'search' => $search,
                'status' => $statusFilter?->value,
                'category' => $request->input('category'),
                'submitted_from' => $request->input('submitted_from'),
                'submitted_to' => $request->input('submitted_to'),
                'per_page' => $perPage,
            ],
        ]);
    }

    private function statusFilter(Request $request, ?JobStatus $statusConstraint): ?JobStatus
    {
        if ($statusConstraint || ! $request->filled('status')) {
            return null;
        }

        if (! in_array($request->input('status'), JobStatus::values(), true)) {
            return null;
        }

        return JobStatus::from($request->input('status'));
    }

    /**
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        $counts = Job::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $normalized = [
            'all' => (int) $counts->sum(),
        ];

        foreach (JobStatus::cases() as $status) {
            $normalized[$status->value] = (int) ($counts[$status->value] ?? 0);
        }

        return $normalized;
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
