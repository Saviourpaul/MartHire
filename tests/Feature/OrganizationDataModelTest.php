<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\ApplicationStatusHistory;
use App\Models\Job;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

afterEach(function (): void {
    app(OrganizationContext::class)->clear();
});

it('fails closed without an organization and scopes recruitment descendants to their organization', function (): void {
    $firstApplication = ApplicationForm::factory()->create();
    $secondApplication = ApplicationForm::factory()->create();
    $firstDocument = ApplicationDocument::factory()->create(['application_form_id' => $firstApplication->id]);
    ApplicationDocument::factory()->create(['application_form_id' => $secondApplication->id]);
    $history = $firstApplication->statusHistories()->create([
        'from_status' => null,
        'to_status' => CandidatePipelineStage::Submitted,
        'changed_by' => $firstApplication->user_id,
        'created_at' => now(),
    ]);

    expect(Job::count())->toBe(0)
        ->and(ApplicationForm::count())->toBe(0)
        ->and(ApplicationDocument::count())->toBe(0)
        ->and(ApplicationStatusHistory::count())->toBe(0)
        ->and($firstDocument->organization_id)->toBe($firstApplication->organization_id)
        ->and($history->organization_id)->toBe($firstApplication->organization_id);

    app(OrganizationContext::class)->set(Organization::findOrFail($firstApplication->organization_id));

    expect(ApplicationForm::pluck('id')->all())->toBe([$firstApplication->id])
        ->and(ApplicationDocument::pluck('id')->all())->toBe([$firstDocument->id])
        ->and(ApplicationStatusHistory::pluck('id')->all())->toBe([$history->id])
        ->and(Job::pluck('id')->all())->toBe([$firstApplication->job_id]);
});

it('rejects cross organization parent links and maintains global job slug uniqueness', function (): void {
    $firstJob = Job::factory()->create(['title' => 'Shared Role']);
    $secondEmployer = User::factory()->employer()->create();
    $secondOrganization = $secondEmployer->organizations()->firstOrFail();
    app(OrganizationContext::class)->set($secondOrganization);
    $secondJob = Job::factory()->create(['title' => 'Shared Role', 'employer_id' => $secondEmployer->id]);

    expect($secondJob->slug)->toBe('shared-role-1');

    expect(fn () => $secondJob->update(['organization_id' => $firstJob->organization_id]))
        ->toThrow(LogicException::class, 'Organization ownership cannot be changed.');

    expect(fn () => ApplicationForm::factory()->create([
        'job_id' => $firstJob->id,
        'organization_id' => $secondOrganization->id,
    ]))->toThrow(LogicException::class, 'Related records must belong to the same organization.');
});

it('keeps activity entries and actor attribution when users and applications are removed', function (): void {
    $application = ApplicationForm::factory()->create();
    $actor = User::factory()->employer()->create();
    $log = ActivityLog::create([
        'organization_id' => $application->organization_id,
        'application_form_id' => $application->id,
        'actor_id' => $actor->id,
        'type' => 'system',
        'payload' => ['event' => 'candidate_viewed'],
    ]);

    expect(fn () => $log->update(['type' => 'pipeline']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class)
        ->and(fn () => ActivityLog::withoutGlobalScope(OrganizationScope::class)->whereKey($log->id)->update(['type' => 'pipeline']))->toThrow(LogicException::class)
        ->and(fn () => ActivityLog::withoutGlobalScope(OrganizationScope::class)->whereKey($log->id)->delete())->toThrow(LogicException::class);

    $actor->delete();
    expect($log->actor->id)->toBe($actor->id);

    $application->delete();
    $storedLog = ActivityLog::withoutGlobalScope(OrganizationScope::class)->findOrFail($log->id);
    expect($storedLog->application_form_id)->toBeNull()
        ->and($storedLog->payload)->toBe(['event' => 'candidate_viewed']);
});

it('backfills one owner organization per employer without merging company names or changing application history', function (): void {
    $owner = User::factory()->employer()->create();
    $noJobsOwner = User::factory()->employer()->create();
    $firstJob = Job::factory()->for($owner, 'employer')->create(['company' => 'Company A']);
    $secondJob = Job::factory()->for($owner, 'employer')->create(['company' => 'Company B']);
    $application = ApplicationForm::factory()->for($firstJob, 'job')->create();
    $document = ApplicationDocument::factory()->create(['application_form_id' => $application->id]);
    $history = $application->statusHistories()->create([
        'from_status' => null, 'to_status' => CandidatePipelineStage::Submitted,
        'changed_by' => $owner->id, 'remarks' => 'Original remarks', 'created_at' => now(),
    ]);

    foreach (['application_status_histories', 'application_documents', 'application_forms', 'job_posts'] as $table) {
        DB::table($table)->update(['organization_id' => null]);
    }
    DB::table('organization_members')->delete();
    DB::table('organizations')->delete();

    $backfill = require database_path('migrations/2026_10_12_192039_backfill_employer_organizations.php');
    $backfill->up();
    $backfill->up();

    $ownerMembership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $owner->id)->firstOrFail();
    expect($ownerMembership->role)->toBe(OrganizationRole::Owner)
        ->and(DB::table('organizations')->count())->toBe(2)
        ->and(DB::table('organization_members')->where('user_id', $noJobsOwner->id)->exists())->toBeTrue()
        ->and(DB::table('job_posts')->where('id', $firstJob->id)->value('organization_id'))->toBe($ownerMembership->organization_id)
        ->and(DB::table('job_posts')->where('id', $secondJob->id)->value('organization_id'))->toBe($ownerMembership->organization_id)
        ->and(DB::table('job_posts')->where('id', $firstJob->id)->value('company'))->toBe('Company A')
        ->and(DB::table('job_posts')->where('id', $secondJob->id)->value('company'))->toBe('Company B')
        ->and(DB::table('application_forms')->where('id', $application->id)->value('reference'))->toBe($application->reference)
        ->and(DB::table('application_documents')->where('id', $document->id)->value('organization_id'))->toBe($ownerMembership->organization_id)
        ->and(DB::table('application_status_histories')->where('id', $history->id)->value('remarks'))->toBe('Original remarks');
});

it('indexes organization ownership on every tenant table', function (): void {
    foreach (['organization_members', 'team_invitations', 'job_post_members', 'activity_logs', 'job_posts', 'application_forms', 'application_documents', 'application_status_histories'] as $table) {
        expect(Schema::hasColumn($table, 'organization_id'))->toBeTrue()
            ->and(collect(Schema::getIndexes($table))->contains(fn (array $index): bool => $index['columns'][0] === 'organization_id'))->toBeTrue();
    }
});

it('keeps provisioned and backfilled organization names within the database limit', function (): void {
    $owner = User::factory()->employer()->create([
        'first_name' => str_repeat('A', 255),
        'last_name' => str_repeat('B', 255),
    ]);
    $organization = $owner->organizations()->firstOrFail();
    expect(mb_strlen($organization->name))->toBe(255);

    DB::table('organization_members')->where('user_id', $owner->id)->delete();
    DB::table('organizations')->where('id', $organization->id)->delete();
    $backfill = require database_path('migrations/2026_10_12_192039_backfill_employer_organizations.php');
    $backfill->up();

    $backfilledOrganization = $owner->organizations()->firstOrFail();
    expect($backfilledOrganization->name)->toBe($organization->name)
        ->and(mb_strlen($backfilledOrganization->name))->toBe(255);
});
