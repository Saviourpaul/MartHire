<?php

use Illuminate\Support\Facades\Storage;

test('an allow-listed public image can be served without a storage symlink', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-images/avatar.jpg', file_get_contents(base_path('tests/Fixtures/applications/profile.png')));

    $this->get(route('public-media.show', ['path' => 'profile-images/avatar.jpg']))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/png')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', config('public-media.cache_control'));
});

test('the media endpoint does not expose paths outside its public image allow-list', function () {
    Storage::fake('public');
    Storage::disk('public')->put('application-documents/1/resume.pdf', 'private resume');
    Storage::disk('public')->put('user-identification-documents/1/passport.jpg', 'private identity document');

    $this->get(route('public-media.show', ['path' => 'application-documents/1/resume.pdf']))->assertNotFound();
    $this->get(route('public-media.show', ['path' => 'user-identification-documents/1/passport.jpg']))->assertNotFound();
    $this->get('/media/profile-images/%2e%2e%2fjob-logos/logo.jpg')->assertNotFound();
});

test('the media endpoint rejects missing and non-image public files', function () {
    Storage::fake('public');
    Storage::disk('public')->put('profile-images/not-an-image.jpg', 'not an image');

    $this->get(route('public-media.show', ['path' => 'profile-images/missing.jpg']))->assertNotFound();
    $this->get(route('public-media.show', ['path' => 'profile-images/not-an-image.jpg']))->assertNotFound();
});
