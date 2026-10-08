<?php

dataset('public pages', [
    'home',
    'about',
    'services',
    'pricing',
    'how-it-works',
]);

test('public pages render production navigation without waitlist controls', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('Browse jobs')
        ->assertDontSee('waitlist', false)
        ->assertDontSee('freewaitlists', false)
        ->assertDontSee('custom-widget', false);
})->with('public pages');
