<?php

// Regression guard: Vite clears only public/asset/marthire/build. Static images,
// social icons and country flags in the parent directory must survive a rebuild.

test('referenced static assets exist in the served asset directory', function () {
    $base = public_path('asset/marthire');

    expect(file_exists($base.'/conversation.png'))->toBeTrue()   // footer wordmark (3 views)
        ->and(file_exists($base.'/glassdoor.svg'))->toBeTrue()   // login social icon
        ->and(file_exists($base.'/indeed.svg'))->toBeTrue()
        ->and(file_exists($base.'/linkedin.svg'))->toBeTrue()
        ->and(file_exists($base.'/flags/ng.svg'))->toBeTrue()    // location picker flags
        ->and(file_exists($base.'/flags/us.svg'))->toBeTrue();
});

test('blade layouts resolve the Vite manifest from the lowercase production build directory', function () {
    foreach ([
        'layouts/app.blade.php',
        'layouts/guest.blade.php',
        'components/layout.blade.php',
    ] as $view) {
        $contents = file_get_contents(resource_path('views/'.$view));

        expect($contents)->toContain("'asset/marthire/build'")->not->toContain("'asset/MartHire/build'");
    }
});
