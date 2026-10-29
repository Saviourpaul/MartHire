<?php

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function (string $firstName, string $lastName, string $fullName) {
    $response = $this->post('/register', [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => 'test@example.com',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('verification.notice', absolute: false));

    $user = User::where('email', 'test@example.com')->sole();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'name' => $fullName,
        'email' => 'test@example.com',
    ]);
    $this->assertTrue($user->isApplicant());
    $this->assertSame(UserStatus::Pending, $user->status);
    $this->assertTrue(Hash::check('Vx9#Lp2qZmT8oWr$', $user->password));
})->with([
    'simple name' => ['Test', 'User', 'Test User'],
    'compound first name' => ['Mary Jane', 'Watson', 'Mary Jane Watson'],
    'maximum length names' => [
        str_repeat('A', 255),
        str_repeat('B', 255),
        str_repeat('A', 255).' '.str_repeat('B', 255),
    ],
]);

test('registration requires each name field', function (string $missingField) {
    $payload = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
    ];

    unset($payload[$missingField]);

    $this->from('/register')
        ->post('/register', $payload)
        ->assertSessionHasErrors($missingField)
        ->assertRedirect('/register');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with(['first_name', 'last_name']);
