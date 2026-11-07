<?php

use App\Enums\NotificationCategory;
use App\Enums\UserStatus;
use App\Mail\WorkflowAlertMail;
use App\Models\NotificationEmailOutbox;
use App\Models\User;
use App\Models\WorkflowNotification;
use App\Services\NotificationEmailService;
use App\Services\WorkspaceUpdateBroadcaster;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

test('applicant registration creates a private inbox notice for active verified admins', function () {
    $admin = User::factory()->admin()->create();
    $suspendedAdmin = User::factory()->admin()->create(['status' => UserStatus::Suspended]);

    $this->post(route('register'), [
        'first_name' => 'New',
        'last_name' => 'Applicant',
        'email' => 'new-applicant@example.test',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
    ])->assertRedirect(route('verification.notice', absolute: false));

    $applicant = User::query()->where('email', 'new-applicant@example.test')->sole();

    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $admin->id,
        'category' => NotificationCategory::AdminApplicantAccount->value,
        'event_key' => 'applicant-registered:'.$applicant->id,
        'subject_type' => 'user',
        'subject_id' => $applicant->id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $suspendedAdmin->id,
        'event_key' => 'applicant-registered:'.$applicant->id,
    ]);

    $this->actingAs($admin)->get(route('notifications.index'))
        ->assertOk()->assertSee('New applicant registered');
});

test('admins can choose email delivery for applicant account activity notices', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('settings.notifications.update'), [
        'email_preferences' => [NotificationCategory::AdminApplicantAccount->value => '1'],
    ])->assertRedirect(route('settings.edit'));

    $this->assertDatabaseHas('notification_preferences', [
        'user_id' => $admin->id,
        'category' => NotificationCategory::AdminApplicantAccount->value,
        'email_enabled' => true,
    ]);
});

test('applicant account profile changes are audited and notify admins without including field values', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create([
        'first_name' => 'Original',
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);

    $this->actingAs($applicant)->patch(route('profile.update'), [
        'first_name' => 'Updated',
        'last_name' => $applicant->last_name,
        'email' => $applicant->email,
        'date_of_birth' => '1995-05-05',
        'phone' => '(416) 555-0123',
        'phone_country' => 'CA',
        'address' => '10 Example Street',
        'country_code' => 'NG',
        'country' => 'Nigeria',
        'state' => 'Lagos',
        'city' => 'Ikeja',
    ])->assertSessionHasNoErrors();

    $notice = WorkflowNotification::query()->where('notifiable_id', $admin->id)
        ->where('category', NotificationCategory::AdminApplicantAccount->value)
        ->where('subject_type', 'user')->where('subject_id', $applicant->id)->sole();

    expect($notice->data['title'])->toBe('Applicant account details updated')
        ->and(json_encode($notice->data))->not->toContain('Updated', '10 Example Street', '4165550123');

    $this->assertDatabaseHas('settings_audit_events', [
        'actor_id' => $applicant->id,
        'user_id' => $applicant->id,
        'action' => 'applicant_profile_updated',
    ]);
});

test('no-op applicant profile saves do not notify admins or create profile activity audits', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create();
    $profile = $applicant->applicantProfile()->create([
        'professional_headline' => 'Product designer',
        'about' => 'Designing useful tools for teams.',
    ]);
    $profile->links()->create(['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/example']);
    $profile->skills()->create(['name' => 'Product design']);
    $profile->employments()->create([
        'company' => 'Example Co',
        'title' => 'Product Designer',
        'start_date' => today()->subYear()->toDateString(),
        'is_current' => true,
    ]);

    $this->actingAs($applicant)->patch(route('profile.details.update'), [
        'professional_headline' => 'Product designer',
        'about' => 'Designing useful tools for teams.',
        'links' => ['linkedin' => 'https://linkedin.com/in/example', 'github' => null, 'website' => null],
    ])->assertSessionHasNoErrors();

    $this->patch(route('profile.skills.update'), ['skills' => ['Product design']])->assertSessionHasNoErrors();

    $employment = $profile->employments()->firstOrFail();
    $this->patch(route('profile.employments.update', $employment), [
        'company' => 'Example Co',
        'title' => 'Product Designer',
        'start_date' => today()->subYear()->toDateString(),
        'is_current' => true,
    ])->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $admin->id,
        'category' => NotificationCategory::AdminApplicantAccount->value,
    ]);
    $this->assertDatabaseMissing('settings_audit_events', [
        'user_id' => $applicant->id,
        'action' => 'applicant_profile_updated',
    ]);
});

test('suspending an applicant notifies other admins and emails the verified applicant', function () {
    Mail::fake();
    Queue::fake();

    $actor = User::factory()->admin()->create();
    $otherAdmin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create();
    $workspaceUpdates = Mockery::mock(WorkspaceUpdateBroadcaster::class);
    $workspaceUpdates->shouldReceive('applicant')->once()->with(
        (int) $applicant->id,
        ['notifications'],
        'Your Marthire account has been suspended. Please contact support if you need help.',
        'warning',
    );
    $workspaceUpdates->shouldReceive('admins')->once()->with(['users', 'notifications']);
    app()->instance(WorkspaceUpdateBroadcaster::class, $workspaceUpdates);

    $this->actingAs($actor)->post(route('admin.users.suspend', $applicant))->assertRedirect();

    expect($applicant->fresh()->status)->toBe(UserStatus::Suspended);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $otherAdmin->id,
        'category' => NotificationCategory::AdminApplicantAccount->value,
        'subject_type' => 'user',
        'subject_id' => $applicant->id,
    ]);
    $this->assertDatabaseMissing('notifications', [
        'notifiable_id' => $actor->id,
        'category' => NotificationCategory::AdminApplicantAccount->value,
        'subject_type' => 'user',
        'subject_id' => $applicant->id,
    ]);

    $statusNotice = WorkflowNotification::query()->where('notifiable_id', $applicant->id)
        ->where('category', NotificationCategory::ApplicantAccountStatus->value)->sole();
    $outbox = NotificationEmailOutbox::query()->where('notification_id', $statusNotice->id)->sole();

    app(NotificationEmailService::class)->deliver($outbox->id);

    Mail::assertSent(WorkflowAlertMail::class, fn (WorkflowAlertMail $mail): bool => $mail->hasTo($applicant->email));
    expect($outbox->fresh()->status)->toBe('sent');
});

test('activating an applicant makes the status notice available in their authenticated inbox', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->applicant()->create(['status' => UserStatus::Suspended]);
    $workspaceUpdates = Mockery::mock(WorkspaceUpdateBroadcaster::class);
    $workspaceUpdates->shouldReceive('applicant')->once()->with(
        (int) $applicant->id,
        ['notifications'],
        'Your Marthire account is active. Sign in to continue.',
        'success',
    );
    $workspaceUpdates->shouldReceive('admins')->once()->with(['users', 'notifications']);
    app()->instance(WorkspaceUpdateBroadcaster::class, $workspaceUpdates);

    $this->actingAs($admin)->post(route('admin.users.activate', $applicant))->assertRedirect();

    expect($applicant->fresh()->status)->toBe(UserStatus::Active);
    $activeApplicant = $applicant->fresh();
    $this->actingAs($activeApplicant)->withSession(['auth.version' => $activeApplicant->authentication_version])
        ->get(route('notifications.index'))
        ->assertOk()->assertSee('Account status updated')->assertSee('active');
});
