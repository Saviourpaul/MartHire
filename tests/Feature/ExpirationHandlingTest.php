<?php

use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Services\OrganizationTeamService;
use App\Support\OrganizationContext;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

beforeEach(function () {
    Route::get('/_expiration/signed', fn () => 'Protected content')->middleware(['web', 'signed'])->name('expiration.signed');
    Route::get('/_expiration/relative', fn () => 'Protected content')->middleware(['web', 'signed:relative'])->name('expiration.relative');
    Route::get('/_expiration/gone', fn () => abort(410, 'Internal detail'))->middleware('web');
    Route::get('/_expiration/forbidden', fn () => abort(403))->middleware('web');
    Route::post('/_expiration/mutation', function () {
        Cache::put('expiration_mutation_ran', true);

        return response()->noContent();
    })->middleware('web');
    Route::get('/_expiration/confirmed', fn () => 'Confirmed content')
        ->middleware(['web', 'auth', 'password.confirm'])->name('expiration.confirmed');
    Route::getRoutes()->refreshNameLookups();
});

it('renders expired verification links without verifying the account', function (bool $debug) {
    config(['app.debug' => $debug]);
    $this->freezeTime();
    $user = User::factory()->unverified()->create();
    Queue::fake();
    $mail = (new VerifyEmail)->toMail($user);
    parse_str(parse_url($mail->viewData['url'], PHP_URL_QUERY), $query);
    expect((int) $query['expires'])->toBe(now()->addMinutes(60)->timestamp);
    $this->travel(61)->minutes();

    $this->actingAs($user)->get($mail->viewData['url'])->assertGone()
        ->assertSee('Verification link expired')
        ->assertSee('Verification links are valid for 60 minutes.')
        ->assertSee('href="'.route('verification.notice').'"', false)
        ->assertDontSee('Symfony\\Component', false)
        ->assertHeader('Referrer-Policy', 'no-referrer');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Queue::assertNothingPushed();
})->with([true, false]);

it('does not classify tampered or cross account verification links as expired', function (string $scenario) {
    $user = User::factory()->unverified()->create();
    $viewer = $scenario === 'cross account' ? User::factory()->unverified()->create() : $user;
    $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
        'id' => $user->id, 'hash' => sha1($user->email),
    ]);

    if ($scenario === 'tampered expiry') {
        $url = preg_replace('/expires=\d+/', 'expires='.now()->subHour()->timestamp, $url);
    }

    $this->actingAs($viewer)->get($url)->assertForbidden()
        ->assertSee('Verification link invalid')
        ->assertDontSee('Verification link expired');
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
})->with(['tampered expiry', 'cross account']);

it('handles absolute and relative signed link expiry and rejects invalid signatures', function (string $route) {
    $url = URL::temporarySignedRoute($route, now()->subMinute(), absolute: $route !== 'expiration.relative');
    $this->get($url)->assertGone()->assertSee('This link has expired or is no longer available.')
        ->assertDontSee('Protected content');
    $this->getJson($url)->assertGone()->assertJsonPath('code', 'link')->assertJsonPath('recovery_url', route('home'));
    $this->get(str_replace('signature=', 'signature=broken', $url))->assertForbidden()
        ->assertSee('This link could not be verified.')->assertDontSee('Protected content');
})->with(['expiration.signed', 'expiration.relative']);

it('handles expiring private local storage URLs without serving their contents', function () {
    Storage::fake('local');
    Storage::disk('local')->put('expiration-test.txt', 'Private contents must stay private');
    $url = URL::temporarySignedRoute('storage.local', now()->subMinute(), ['path' => 'expiration-test.txt'], absolute: false);
    $this->get($url)->assertGone()->assertDontSee('Private contents must stay private');
});

it('requires the signing mode configured on the route even when a link is expired', function (string $route) {
    $url = URL::temporarySignedRoute($route, now()->subMinute(), absolute: $route === 'expiration.relative');
    $this->get($url)->assertForbidden()->assertSee('This link could not be verified.')
        ->assertDontSee('Protected content');
})->with(['expiration.signed', 'expiration.relative']);

it('rejects expired temporary upload links without writing a file', function () {
    Storage::fake('local');
    $url = URL::temporarySignedRoute('storage.local.upload', now()->subMinute(), [
        'path' => 'expired-upload.txt', 'upload' => true,
    ], absolute: false);
    $this->putJson($url, ['contents' => 'Must not be stored'])->assertGone()->assertJsonPath('code', 'link');
    Storage::disk('local')->assertMissing('expired-upload.txt');
});

it('returns a clear 419 for stale form tokens without processing the form', function (bool $debug) {
    config(['app.debug' => $debug]);
    $this->app->instance('env', 'local');
    $this->withSession(['_token' => 'current-token']);
    $this->post('/_expiration/mutation', ['_token' => 'stale-token', 'password' => 'Never echo this'])
        ->assertStatus(419)->assertSee('Session or form expired')
        ->assertSee('Your request was not processed.')
        ->assertSee('href="'.route('login').'"', false)
        ->assertDontSee('Never echo this')->assertDontSee('TokenMismatchException');
    expect(Cache::has('expiration_mutation_ran'))->toBeFalse();
    $this->postJson('/_expiration/mutation', ['_token' => 'stale-token'])
        ->assertStatus(419)->assertJsonPath('code', 'session')->assertJsonPath('recovery_url', route('login'))
        ->assertJsonMissingPath('trace');
})->with([true, false]);

it('offers authenticated users a safe dashboard recovery after CSRF expiry', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $this->app->instance('env', 'local');
    $this->post('/_expiration/mutation', ['_token' => 'stale-token'])
        ->assertStatus(419)->assertSee('href="'.route('dashboard').'"', false);
    expect(Cache::has('expiration_mutation_ran'))->toBeFalse();
});

it('preserves intended destinations and offers login recovery for lost sessions', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'))
        ->assertSessionHas('url.intended', route('dashboard'))
        ->assertSessionHas('info', __('expiration.sign_in'));
    $this->get(route('login'))->assertOk()->assertSee(__('expiration.sign_in'));
    $this->getJson(route('dashboard'))->assertUnauthorized()
        ->assertJsonPath('code', 'authentication_required')->assertJsonPath('recovery_url', route('login'));
});

it('requires fresh password confirmation after its configured timeout', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->withSession([
        'auth.password_confirmed_at' => now()->subSeconds(config('auth.password_timeout') + 1)->timestamp,
    ])->get(route('expiration.confirmed'))->assertRedirect(route('password.confirm'));
    $this->get(route('password.confirm'))->assertOk()->assertSee('Confirm your password');
    $this->post(route('password.confirm'), ['password' => 'password'])->assertRedirect(route('expiration.confirmed'));
    $this->get(route('expiration.confirmed'))->assertOk()->assertSee('Confirmed content');
});

it('recovers from an expired or invalid Google sign in session without creating an account', function () {
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
    Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);
    $count = User::count();
    $this->get(route('auth.google-callback'))->assertStatus(419)
        ->assertSee('Google sign-in session expired')
        ->assertSee('href="'.route('auth.google').'"', false)
        ->assertDontSee('InvalidStateException');
    $this->assertGuest();
    expect(User::count())->toBe($count);
});

it('uses a generic gone page and keeps missing pages and permissions distinct', function () {
    $this->get('/_expiration/gone')->assertGone()->assertSee('Link expired or unavailable')
        ->assertDontSee('Invitation')->assertDontSee('Internal detail');
    $this->get('/_expiration/forbidden')->assertForbidden();
    $this->get('/_expiration/not-found')->assertNotFound()->assertSee('Page not found')
        ->assertDontSee('Session Has Expired')->assertDontSee('Session or form expired');
});

it('uses configured invitation lifetimes and shares their actual expiry in notifications', function () {
    $this->freezeTime();
    config(['auth.invitations.expire' => 2]);
    $owner = User::factory()->employer()->create();
    $organization = $owner->organizationMemberships()->withoutGlobalScopes()->firstOrFail()->organization;
    app(OrganizationContext::class)->set($organization);
    Notification::fake();
    $invitation = app(OrganizationTeamService::class)->invite($organization, $owner, 'new@example.test', OrganizationRole::Recruiter);
    expect($invitation->expires_at->toDateTimeString())->toBe(now()->addMinutes(2)->toDateTimeString());
    Notification::assertSentOnDemand(TeamInvitationNotification::class,
        function ($notification, $channels, $notifiable) use ($invitation): bool {
            $mail = $notification->toMail($notifiable);
            expect(implode(' ', [...$mail->introLines, ...$mail->outroLines]))
                ->toContain('expires on '.$invitation->expires_at->format('M j, Y, g:i A'));

            return true;
        });
});

it('loads recovery controls for background requests without exposing invitation credentials', function () {
    $user = User::factory()->employer()->create();
    $this->actingAs($user)->get(route('jobs'))->assertOk()
        ->assertSee('assets/js/request-recovery.js', false)
        ->assertSee('id="request-recovery"', false)
        ->assertSee('data-recovery-action', false);
});

it('recovers expired application links while retaining submitted applications and job details', function (string $reason) {
    $job = Job::factory()->create([
        'status' => $reason === 'closed' ? 'closed' : 'open',
        'due_date' => $reason === 'deadline' ? today()->subDay() : today()->addWeek(),
    ]);
    $applicant = User::factory()->create();
    $application = ApplicationForm::factory()->create(['job_id' => $job->id, 'user_id' => $applicant->id]);
    $this->actingAs($applicant)->get(route('applications.create', $job))->assertGone()
        ->assertSee('Applications closed')->assertSee('href="'.route('client.jobs-listings').'"', false);
    $this->postJson(route('applications.store', $job), [])->assertGone()->assertJsonPath('code', 'application_closed');
    $this->get(route('job-details', $job))->assertOk();
    $this->get(route('client.applications.show', $application))->assertOk()->assertSee($application->reference);
    $this->assertDatabaseCount('application_forms', 1);
})->with(['closed', 'deadline']);
