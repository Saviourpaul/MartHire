<?php

namespace App\Http\Controllers;

use App\Enums\JobStatus;
use App\Http\Requests\CloseJobRequest;
use App\Models\Job;
use App\Support\JobDescription;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class JobController extends Controller
{
    public function listings(Request $request): View
    {
        return view('client.jobs-listings', $this->publicListingData($request, 6));
    }

    public function browse(Request $request): View|JsonResponse
    {
        $data = $this->publicListingData($request, 6);

        $data['categories'] = Job::query()
            ->acceptingApplications()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        if ($request->ajax()) {
            return response()->json([
                'html' => view('Browse-jobs', $data)->fragment('jobs-results'),
                'total' => $data['jobs']->total(),
            ]);
        }

        return view('Browse-jobs', $data);
    }

    public function index(Request $request): View
    {
        $sortableColumns = ['title', 'company', 'category', 'location', 'employment_type', 'start_date', 'due_date', 'created_at'];
        $sortColumn = $request->input('sort') && in_array($request->input('sort'), $sortableColumns, true)
            ? $request->input('sort')
            : 'created_at';
        $sortDirection = $request->input('direction') === 'asc' ? 'asc' : 'desc';

        $jobs = Job::query()
            ->where('employer_id', $request->user()->id)
            ->when(is_string($request->query('status')) && JobStatus::tryFrom($request->query('status')), fn ($query) => $query->status($request->query('status')))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search')->trim().'%';

                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', $search)
                        ->orWhere('company', 'like', $search)
                        ->orWhere('category', 'like', $search)
                        ->orWhere('location', 'like', $search)
                        ->orWhere('employment_type', 'like', $search)
                        ->orWhere('description', 'like', $search);
                });
            })
            ->orderBy($sortColumn, $sortDirection)
            ->withCount('applications')
            ->paginate(10)
            ->withQueryString();

        return view('employer.jobs.index', [
            'jobs' => $jobs,
            'sortColumn' => $sortColumn,
            'sortDirection' => $sortDirection,
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate($this->rules($request));

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('job-logos', 'public');
        }

        $data['status'] = JobStatus::Open;

        $request->user()->jobs()->create($data);

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Job published and open for applications.');

            return response()->json([
                'message' => 'Job published and open for applications.',
                'redirect' => route('jobs'),
            ]);
        }

        return redirect()
            ->route('jobs')
            ->with('success', 'Job published and open for applications.');
    }

    public function update(Request $request, Job $job): RedirectResponse|JsonResponse
    {
        $this->ensureEmployerOwnsJob($request, $job);

        Gate::authorize('update', $job);
        $data = $request->validate($this->rules($request));
        if ($request->hasFile('logo')) {
            unset($data['logo']);
        }

        $oldLogoPath = null;
        $newLogoPath = null;
        try {
            $reopened = DB::transaction(function () use ($request, $job, $data, &$oldLogoPath, &$newLogoPath): bool {
                $currentJob = Job::query()->lockForUpdate()->findOrFail($job->id);
                $reopen = $currentJob->isSuspended();
                $previousLogoPath = $currentJob->storedLogoPath();
                $currentJob->fill($data);

                if ($reopen) {
                    if ($currentJob->due_date->isBefore(today())) {
                        throw ValidationException::withMessages(['due_date' => 'Choose a current or future deadline before reopening.']);
                    }
                    $currentJob->status = JobStatus::Open;
                }

                if ($request->hasFile('logo')) {
                    $oldLogoPath = $previousLogoPath;
                    $newLogoPath = $request->file('logo')->store('job-logos', 'public');
                    $currentJob->logo = $newLogoPath;
                } elseif ($currentJob->isDirty('logo')) {
                    $oldLogoPath = $previousLogoPath;
                }
                $currentJob->save();

                return $reopen;
            });
        } catch (Throwable $exception) {
            if ($newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }
            throw $exception;
        }

        if ($oldLogoPath !== null) {
            Storage::disk('public')->delete($oldLogoPath);
        }
        $message = $reopened ? 'Corrections saved. Job reopened for applications.' : 'Job updated successfully.';

        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json([
                'message' => $message,
                'redirect' => route('jobs.show', $job),
            ]);
        }

        return redirect()
            ->route('jobs')
            ->with('success', $message);
    }

    public function close(CloseJobRequest $request, Job $job): RedirectResponse|JsonResponse
    {
        $updated = Job::query()->whereKey($job->id)->where('status', '<>', JobStatus::Closed->value)
            ->update(['status' => JobStatus::Closed]);

        if ($updated === 0) {
            throw ValidationException::withMessages(['confirm_closure' => 'This job is already closed.']);
        }

        if ($request->expectsJson()) {
            $request->session()->flash('success', 'Job closed. Existing applications remain available.');

            return response()->json(['message' => 'Job closed. Existing applications remain available.', 'redirect' => route('jobs.show', $job)]);
        }

        return redirect()->route('jobs.show', $job)->with('success', 'Job closed. Existing applications remain available.');
    }

    public function destroy(Request $request, Job $job): RedirectResponse|JsonResponse
    {
        $this->ensureEmployerOwnsJob($request, $job);

        $this->deleteStoredLogo($job);
        $job->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Job deleted successfully.',
                'redirect' => route('jobs'),
            ]);
        }

        return redirect()
            ->route('jobs')
            ->with('success', 'Job deleted successfully.');
    }

    public function employerShow(Request $request, Job $job): View
    {
        $this->ensureEmployerOwnsJob($request, $job);

        $job->loadCount('applications');

        return view('employer.jobs.show', [
            'job' => $job,
        ]);
    }

    public function show(Request $request, Job $job): View
    {
        $job->loadMissing(['employer:id,first_name,last_name'])
            ->loadCount('applications');

        if ($job->isSuspended()) {
            $user = $request->user();

            abort_unless(
                $user?->isAdmin() || ($user?->isEmployer() && $job->employer_id === $user->id),
                404
            );
        }

        $existingApplication = null;

        if ($request->user()?->isApplicant()) {
            $existingApplication = $job->applications()
                ->where('user_id', $request->user()->id)
                ->first();
        }

        $view = $request->user()?->isApplicant()
            ? 'client.job-details'
            : 'job-details';

        return view($view, [
            'job' => $job,
            'existingApplication' => $existingApplication,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => [
                'bail', 'required', 'string', 'max:'.JobDescription::MAX_LENGTH,
                static function (string $attribute, mixed $value, Closure $fail): void {
                    if (! app(JobDescription::class)->hasText($value)) {
                        $fail('The description must contain text about the role.');
                    }
                },
            ],
            'company' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'employment_type' => ['required', 'string', Rule::in(array_keys(Job::employmentTypeOptions()))],
            'logo' => $request->hasFile('logo')
                ? ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:2048']
                : ['nullable', 'string', 'max:2048'],
            'start_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:start_date', ...($request->routeIs('jobs.store') ? ['after_or_equal:today'] : [])],
        ];
    }

    private function deleteStoredLogo(Job $job): void
    {
        if ($path = $job->storedLogoPath()) {
            Storage::disk('public')->delete($path);
        }
    }

    private function ensureEmployerOwnsJob(Request $request, Job $job): void
    {
        abort_unless($job->employer_id === $request->user()->id, 403);
    }

    /** @return array{jobs: LengthAwarePaginator, appliedJobIds: Collection, search: string, category: string, employmentType: string} */
    private function publicListingData(Request $request, int $perPage): array
    {
        $searchInput = $request->query('search', '');
        $categoryInput = $request->query('category', '');
        $employmentTypeInput = $request->query('employment_type', '');
        $search = is_string($searchInput) ? trim(mb_substr($searchInput, 0, 120)) : '';
        $category = is_string($categoryInput) ? trim(mb_substr($categoryInput, 0, 255)) : '';
        $employmentType = is_string($employmentTypeInput) ? $employmentTypeInput : '';
        $employmentTypes = Job::employmentTypeOptions();

        if (! array_key_exists($employmentType, $employmentTypes)) {
            $employmentType = '';
        }

        $jobs = Job::query()
            ->acceptingApplications()
            ->with('employer:id,first_name,last_name')
            ->withCount('applications')
            ->when($search !== '', function ($query) use ($search) {
                foreach (array_filter(preg_split('/\s+/', $search) ?: []) as $term) {
                    $like = '%'.$term.'%';

                    $query->where(function ($query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('company', 'like', $like)
                            ->orWhere('category', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhere('employment_type', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhereHas('employer', function ($query) use ($like) {
                                $query->where('first_name', 'like', $like)
                                    ->orWhere('last_name', 'like', $like);
                            });
                    });
                }
            })
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->when($employmentType !== '', fn ($query) => $query->where('employment_type', $employmentType))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $user = $request->user();
        $pageJobIds = $jobs->getCollection()->modelKeys();
        $appliedJobIds = $user?->isApplicant()
            ? $user->applications()->whereIn('job_id', $pageJobIds)->pluck('job_id')
            : collect();

        return compact('jobs', 'appliedJobIds', 'search', 'category', 'employmentType');
    }
}
