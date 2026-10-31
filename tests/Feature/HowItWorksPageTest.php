<?php

test('how it works is public and links the real recruitment journey to working destinations', function () {
    $response = $this->get(route('how-it-works'))->assertOk()->assertViewIs('HowItWorks')
        ->assertSee('How Marthire')->assertSee('Build a structured workflow')->assertSee('Centralize every application')
        ->assertSee('Guide candidates forward')->assertSee('Confirm selection and close the role')
        ->assertSee('Mark the interview completed')->assertSee('href="'.route('contact').'"', false)
        ->assertSee('href="'.route('workable-jobs.index').'"', false)
        ->assertDontSee('NearbyWorks')->assertDontSee('asset/nearbyworks', false);

    $html = $response->getContent();
    preg_match_all('/href="#([^"]+)"/', $html, $anchors);
    foreach ($anchors[1] as $id) {
        expect($html)->toContain('id="'.$id.'"');
    }
    expect(substr_count($html, 'class="nw-how-feature__visual"'))->toBe(3)
        ->and(substr_count($html, 'class="nw-how-step__image"'))->toBe(4);
});

test('public navigation and metadata use Marthire and expose how it works', function () {
    foreach (['/', '/About', '/ContactUs', '/login', '/register', '/how-it-works'] as $url) {
        $this->get($url)->assertOk()->assertSee('| Marthire', false)
            ->assertSee('href="'.route('how-it-works').'"', false)
            ->assertDontSee('NearbyWorks')->assertDontSee('Nearbywork@gmail.com');
    }
});

test('every how it works illustration is served from the local Marthire assets', function () {
    $html = $this->get(route('how-it-works'))->assertOk()->getContent();
    preg_match_all('/<img[^>]+src="([^"]*\/asset\/marthire\/how-it-works\/([^"]+))"/', $html, $images);
    expect($images[2])->toHaveCount(7);
    foreach ($images[2] as $filename) {
        $path = public_path('asset/marthire/how-it-works/'.$filename);
        expect(is_file($path))->toBeTrue();
        $size = getimagesize($path);
        expect($size[2])->toBe(IMAGETYPE_PNG);
    }
});
