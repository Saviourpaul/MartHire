<?php

use App\Models\User;

test('all layouts provide the shared alert host even without a flash notification', function () {
    foreach ([route('home'), route('login')] as $url) {
        $response = $this->get($url)->assertOk()->assertSee('data-toast-list', false)->assertSee('data-alert-dialog', false);
        expect(substr_count($response->getContent(), 'data-alert-dialog'))->toBe(1);
    }
    $response = $this->actingAs(User::factory()->create())->get(route('profile.edit'))
        ->assertOk()->assertSee('data-toast-list', false)->assertSee('data-alert-dialog', false);
    expect(substr_count($response->getContent(), 'data-alert-dialog'))->toBe(1);
});

test('login and logout feedback uses one shared notification in account and public layouts', function () {
    $user = User::factory()->create();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->get(route('dashboard'))->assertRedirect(route('client.dashboard'));
    $dashboard = $this->get(route('client.dashboard'))->assertOk()
        ->assertSee('data-position="bottom-right"', false)
        ->assertSee('data-toast-type="success"', false)
        ->assertSee('Logged in successfully.');

    expect(substr_count($dashboard->getContent(), 'Logged in successfully.'))->toBe(1);

    $this->post(route('logout'))->assertRedirect('/');
    $home = $this->get(route('home'))->assertOk()
        ->assertSee('data-position="bottom-right"', false)
        ->assertSee('Logged out successfully.');
    expect(substr_count($home->getContent(), 'Logged out successfully.'))->toBe(1);
    $this->get(route('home'))->assertOk()->assertDontSee('Logged out successfully.');
});

test('the auth layout renders each flash severity and translates machine statuses', function () {
    $response = $this->withSession([
        'success' => 'Saved successfully.',
        'error' => 'Could not complete that action.',
        'warning' => 'Check the details before continuing.',
        'info' => 'Your request is being processed.',
        'status' => 'password-updated',
    ])->get(route('login'))->assertOk()
        ->assertSee('Your password has been updated.')
        ->assertDontSee('password-updated');

    foreach (['success', 'error', 'warning', 'info'] as $type) {
        $response->assertSee('data-toast-type="'.$type.'"', false);
    }
    expect(substr_count($response->getContent(), 'class="nw-toasts"'))->toBe(1);
});

test('notification messages are escaped and repeated status text is shown only once', function () {
    $message = '<script>alert("toast")</script>';
    $response = $this->withSession(['success' => $message, 'status' => $message])
        ->get(route('login'))->assertOk()
        ->assertSee(e($message), false)
        ->assertDontSee($message, false);

    expect(substr_count($response->getContent(), e($message)))->toBe(1);
});

test('verification status uses readable notification copy without replacing validation errors', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->withSession(['status' => 'verification-code-sent'])
        ->get(route('verification.notice'))->assertOk()
        ->assertSee('A new verification code has been sent to your email address.')
        ->assertSee('data-toast-type="success"', false)
        ->assertDontSee('verification-code-sent');

    $this->post(route('verification.verify'), ['code' => 'invalid'])
        ->assertSessionHasErrors('code');
    $this->get(route('verification.notice'))->assertOk()->assertSee('id="code-error"', false);
});
