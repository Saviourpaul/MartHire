<?php

use App\Enums\InterviewTemplateType;
use App\Enums\OrganizationRole;
use App\Models\EmailTemplate;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\DB;

function installSystemInterviewTemplates(): void
{
    foreach (config('interviews.defaults') as $type => $template) {
        DB::table('email_templates')->updateOrInsert(
            ['system_key' => $type],
            ['organization_id' => null, 'type' => $type, 'name' => $template['name'], 'subject' => $template['subject'], 'body' => $template['body'], 'is_active' => true, 'is_system' => true, 'created_at' => now(), 'updated_at' => now()]
        );
    }
}

test('an authorized organization member can view and override interview email templates', function () {
    [$organization, $owner] = organizationWithOwner();
    installSystemInterviewTemplates();

    $this->actingAs($owner)->get(route('organisations.email-templates.index'))
        ->assertOk()
        ->assertSee('Interview email templates')
        ->assertSee('Online interview invitation');

    $payload = [
        'subject' => 'Interview with {{ organization_name }} for {{ job_title }}',
        'body' => "Hi {{ candidate_name }},\n\nJoin us at {{ meeting_link }}.",
        'is_active' => '1',
    ];

    $this->actingAs($owner)->put(route('organisations.email-templates.update', InterviewTemplateType::InterviewOnline), $payload)
        ->assertRedirect(route('organisations.email-templates.index'))
        ->assertSessionHasNoErrors();

    $override = app(OrganizationContext::class)->runInOrganization($organization, fn () => EmailTemplate::query()
        ->where('organization_id', $organization->id)->where('type', InterviewTemplateType::InterviewOnline)->sole());

    expect($override->subject)->toBe($payload['subject'])
        ->and($override->body)->toBe($payload['body'])
        ->and($override->is_active)->toBeTrue();
});

test('organization members without email-template permission cannot access or change templates', function () {
    [$organization] = organizationWithOwner();
    installSystemInterviewTemplates();
    $recruiter = addOrganizationMember($organization, OrganizationRole::Recruiter)->user;

    $this->actingAs($recruiter)->get(route('organisations.email-templates.index'))->assertForbidden();
    $this->actingAs($recruiter)->put(route('organisations.email-templates.update', InterviewTemplateType::InterviewOnline), [
        'subject' => 'Attempt', 'body' => 'Hi {{ candidate_name }} {{ meeting_link }}',
    ])->assertForbidden();
});

test('organization templates retain renderer placeholder protections', function () {
    [, $owner] = organizationWithOwner();
    installSystemInterviewTemplates();

    $this->actingAs($owner)->from(route('organisations.email-templates.index'))
        ->put(route('organisations.email-templates.update', InterviewTemplateType::InterviewOnline), [
            'subject' => 'Interview invitation', 'body' => 'Hi {{ candidate_name }}',
        ])
        ->assertRedirect(route('organisations.email-templates.index'))
        ->assertSessionHasErrors('body');
});
