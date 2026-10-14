<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateJobTeamRequest;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class JobTeamController extends Controller
{
    public function update(UpdateJobTeamRequest $request, Job $job, ActivityLogger $logs): RedirectResponse
    {  // update Happens inside a database transaction to ensure that the job team is updated atomically and consistently. The transaction ensures that either all changes are applied or none are, maintaining data integrity.
        DB::transaction(function () use ($request, $job, $logs): void {
            Organization::whereKey($job->organization_id)->lockForUpdate()->firstOrFail();
            Gate::authorize('manageTeam', $job);

            // it reads the validated user_ids from the request, converts them to integers, removes duplicates, and ensures they are unique and valid.
            $ids = collect($request->validated('user_ids', []))->map(fn ($id): int => (int) $id)->unique()->values();
            $eligible = OrganizationMember::where('organization_id', $job->organization_id)->where('status', 'active')
                ->whereHas('user', fn ($users) => $users->where('status', 'active')->where('role', 'employer')->whereNull('users.deleted_at')->whereNotNull('email_verified_at'))
                ->whereIn('user_id', $ids)->pluck('user_id');

            if ($eligible->count() !== $ids->count()) {
                throw ValidationException::withMessages(['user_ids' => 'Choose active members of this organization.']);
            }

            $previous = JobPostMember::where('job_id', $job->id)->pluck('user_id')->all();
            JobPostMember::where('job_id', $job->id)->whereNotIn('user_id', $ids)->delete();

            foreach ($ids as $id) {
                JobPostMember::firstOrCreate(['organization_id' => $job->organization_id, 'job_id' => $job->id, 'user_id' => $id]);
            }

            $logs->record($job->organization_id, null, $request->user(), 'system', [
                'action' => 'job_team_updated',
                'job_id' => $job->id,
                'from_user_ids' => $previous,
                'to_user_ids' => $ids->all(),
            ]);
        });

        return back()->with('success', 'Hiring team updated.');
    }
}
