<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendVerificationEmail;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('registration form exposes password and confirmation fields', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('Password')
        ->assertSee('Confirm Password');
});

test('new users register as pending applicants and queue verification delivery', function () {
    Queue::fake();

    $response = $this->post('/register', [
        'first_name' => 'Test',
        'last_name' => 'User',
        'username' => 'test-user',
        'email' => 'test@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'test@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Applicant)
        ->and($user->status)->toBe(UserStatus::Pending)
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->approved_at)->toBeNull();

    Queue::assertPushed(SendVerificationEmail::class, fn (SendVerificationEmail $job): bool => $job->userId === $user->id);
});

test('registration keeps an intended job application until verification completes', function () {
    Queue::fake();
    $job = Job::factory()->approved()->create();

    $response = $this
        ->withSession(['url.intended' => route('applications.create', $job)])
        ->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test-user',
            'email' => 'test@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));
});

test('active applicants can access their dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Welcome Back');
});
