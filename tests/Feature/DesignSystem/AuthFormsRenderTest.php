<?php

test('login page renders with its submit action', function () {
    $this->get(route('login'))->assertOk()->assertSee('Log in');
});

test('register page renders with its submit action', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create account');
});

test('forgot-password page renders with its submit action', function () {
    $this->get(route('password.request'))->assertOk()->assertSee('Send reset link');
});
