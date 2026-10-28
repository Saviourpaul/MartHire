<?php

// Regression guard: the Vite build writes into public/asset/nearbyworks/assets/, a
// directory that also holds hand-placed static assets (the footer image, social icons,
// country flags). A build that empties the output dir would delete them, breaking the
// live site. These must survive every rebuild.

test('referenced static assets exist in the served asset directory', function () {
    $base = public_path('asset/nearbyworks');

    expect(file_exists($base.'/conversation.png'))->toBeTrue()   // footer wordmark (3 views)
        ->and(file_exists($base.'/glassdoor.svg'))->toBeTrue()   // login social icon
        ->and(file_exists($base.'/indeed.svg'))->toBeTrue()
        ->and(file_exists($base.'/linkedin.svg'))->toBeTrue()
        ->and(file_exists($base.'/flags/ng.svg'))->toBeTrue()    // location picker flags
        ->and(file_exists($base.'/flags/us.svg'))->toBeTrue();
});
