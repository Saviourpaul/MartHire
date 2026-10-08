<?php

use App\Enums\UserStatus;
use App\Jobs\SendVerificationEmail;
use App\Jobs\SendWelcomeEmail;
use App\Mail\WelcomeEmail;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

function verificationUrl(User $user, ?string $email = null, mixed $expiration = null): string
{
    return URL::temporarySignedRoute('verification.verify', $expiration ?? now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($email ?? $user->getEmailForVerification()),
    ]);
}

test('the verification delivery job sends Laravel standard verification mail', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    (new SendVerificationEmail($user->id))->handle();

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('the verification delivery job skips accounts that are already verified', function () {
    Notification::fake();
    $user = User::factory()->create();

    (new SendVerificationEmail($user->id))->handle();

    Notification::assertNothingSent();
});

test('an unverified user remains authenticated only for the verification flow', function () {
    $user = User::factory()->unverified()->create();
    $job = Job::factory()->approved()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($user)
        ->get(route('applications.create', $job))
        ->assertRedirect(route('verification.notice'));

    $this->assertAuthenticatedAs($user);
});

test('a legacy active user without a verification timestamp cannot access the dashboard', function () {
    $user = User::factory()->unverified()->create([
        'status' => UserStatus::Active,
        'approved_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

test('password login sends an unverified user to the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('verification.notice', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('verifying activates once and queues one welcome email after thirty seconds', function () {
    Queue::fake();
    $now = Carbon::parse('2026-10-07 12:00:00');
    Carbon::setTestNow($now);

    try {
        $user = User::factory()->unverified()->create();
        $url = verificationUrl($user);

        $this->actingAs($user)->get($url)->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        expect($user->hasVerifiedEmail())->toBeTrue()
            ->and($user->status)->toBe(UserStatus::Active)
            ->and($user->approved_at)->not->toBeNull()
            ->and($user->welcome_email_queued_at)->not->toBeNull();

        Queue::assertPushed(SendWelcomeEmail::class, function (SendWelcomeEmail $job) use ($user, $now): bool {
            return $job->userId === $user->id
                && Carbon::parse($job->delay)->equalTo($now->copy()->addSeconds(30));
        });

        $this->actingAs($user->fresh())->get($url)->assertRedirect(route('dashboard', absolute: false));
        Queue::assertPushed(SendWelcomeEmail::class, 1);
    } finally {
        Carbon::setTestNow();
    }
});

test('welcome delivery sends once and records success only after the mail is accepted', function () {
    Mail::fake();
    $user = User::factory()->create(['welcome_email_queued_at' => now()]);

    (new SendWelcomeEmail($user->id))->handle();

    Mail::assertSent(WelcomeEmail::class, 1);
    expect($user->fresh()->welcome_email_sent_at)->not->toBeNull();

    (new SendWelcomeEmail($user->id))->handle();
    Mail::assertSent(WelcomeEmail::class, 1);
});

test('a welcome transport failure leaves the message eligible for a queue retry', function () {
    $user = User::factory()->create(['welcome_email_queued_at' => now()]);

    Mail::shouldReceive('to')->once()->andReturnSelf();
    Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('mail transport unavailable'));

    expect(fn () => (new SendWelcomeEmail($user->id))->handle())
        ->toThrow(RuntimeException::class);

    expect($user->fresh()->welcome_email_sent_at)->toBeNull();
});

test('guest, expired, invalid, and cross-account verification links never activate an account', function () {
    $user = User::factory()->unverified()->create();
    $otherUser = User::factory()->unverified()->create();
    $validUrl = verificationUrl($user);

    $this->get($validUrl)->assertRedirect(route('login'));
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($otherUser)
        ->get($validUrl)
        ->assertRedirect(route('verification.notice'));
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)
        ->get(verificationUrl($user, 'wrong@example.com'))
        ->assertRedirect(route('verification.notice'));
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->actingAs($user)
        ->get(verificationUrl($user, expiration: now()->subMinute()))
        ->assertRedirect(route('verification.notice'));
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
