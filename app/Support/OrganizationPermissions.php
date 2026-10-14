<?php

namespace App\Support;

use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class OrganizationPermissions
{
    /** @var array<string, list<string>> */
    public const ROLE_PERMISSIONS = [
        'owner' => ['view_jobs', 'create_jobs', 'edit_jobs', 'delete_jobs', 'view_candidates', 'view_documents', 'assign_candidates', 'move_candidates', 'select_candidates', 'add_notes', 'submit_evaluations', 'view_all_evaluations', 'create_tasks', 'own_tasks', 'manage_team', 'hiring_settings', 'assign_job_members'],
        'hr_manager' => ['view_jobs', 'create_jobs', 'edit_jobs', 'delete_jobs', 'view_candidates', 'view_documents', 'assign_candidates', 'move_candidates', 'select_candidates', 'add_notes', 'submit_evaluations', 'view_all_evaluations', 'create_tasks', 'own_tasks', 'manage_team', 'assign_job_members'],
        'recruiter' => ['view_jobs', 'create_jobs', 'edit_jobs', 'view_candidates', 'view_documents', 'assign_candidates', 'move_candidates', 'add_notes', 'submit_evaluations', 'view_all_evaluations', 'create_tasks', 'own_tasks'],
        'hiring_manager' => ['view_jobs', 'view_candidates', 'view_documents', 'move_candidates', 'select_candidates', 'add_notes', 'submit_evaluations', 'view_all_evaluations', 'create_tasks', 'own_tasks'],
        'interviewer' => ['view_jobs', 'view_candidates', 'add_notes', 'submit_evaluations', 'own_tasks'],
    ];

    public function __construct(private OrganizationContext $context) {}

    public function membership(User $user): ?OrganizationMember
    {
        if (! $user->isEmployer() || ! $user->isActive() || ! $user->hasVerifiedEmail() || $user->trashed() || ! $this->context->organizationId()) {
            return null;
        }

        return OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $this->context->organizationId())->where('user_id', $user->id)
            ->where('status', 'active')->first();
    }

    public function can(User $user, string $permission, ?Job $job = null, ?ApplicationForm $application = null): bool
    {
        $membership = $this->membership($user);

        if (! $membership || ! in_array($permission, self::ROLE_PERMISSIONS[$membership->role->value] ?? [], true)) {
            return false;
        }

        if ($job && ! $this->canAccessJob($user, $job, $membership)) {
            return false;
        }

        return ! $application || ($application->organization_id === $membership->organization_id
            && $this->canAccessJob($user, $application->job, $membership)
            && ($membership->role !== OrganizationRole::Interviewer || $this->hasReachedInterview($application)));
    }

    public function canAccessJob(User $user, Job $job, ?OrganizationMember $membership = null): bool
    {
        $membership ??= $this->membership($user);

        if (! $membership || $job->organization_id !== $membership->organization_id) {
            return false;
        }

        return in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::HrManager], true)
            || JobPostMember::withoutGlobalScope(OrganizationScope::class)->where('organization_id', $membership->organization_id)
                ->where('job_id', $job->id)->where('user_id', $user->id)->exists();
    }

    public function scopeJobs(Builder $query, User $user): Builder
    {
        $membership = $this->membership($user);

        if (! $membership) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($query->getModel()->qualifyColumn('organization_id'), $membership->organization_id);

        if (! in_array($membership->role, [OrganizationRole::Owner, OrganizationRole::HrManager], true)) {
            $query->whereIn($query->getModel()->qualifyColumn('id'), JobPostMember::query()->select('job_id')->where('user_id', $user->id));
        }

        return $query;
    }

    public function scopeApplications(Builder $query, User $user): Builder
    {
        $membership = $this->membership($user);

        if (! $membership) {
            return $query->whereRaw('1 = 0');
        }

        $query->where($query->getModel()->qualifyColumn('organization_id'), $membership->organization_id)
            ->whereHas('job', fn (Builder $jobs): Builder => $this->scopeJobs($jobs, $user));

        if ($membership->role === OrganizationRole::Interviewer) {
            $query->where(function (Builder $candidates): void {
                $candidates->whereIn('status', [CandidatePipelineStage::Interview->value, CandidatePipelineStage::Selected->value])
                    ->orWhereHas('statusHistories', fn (Builder $history): Builder => $history->where('to_status', CandidatePipelineStage::Interview->value));
            });
        }

        return $query;
    }

    private function hasReachedInterview(ApplicationForm $application): bool
    {
        return in_array($application->status, [CandidatePipelineStage::Interview, CandidatePipelineStage::Selected], true)
            || $application->statusHistories()->where('to_status', CandidatePipelineStage::Interview->value)->exists();
    }
}
