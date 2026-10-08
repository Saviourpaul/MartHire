<?php

use App\Services\LocationSnapshots;

it('validates checksums and reads every bundled partition independently', function () {
    $snapshots = app(LocationSnapshots::class);
    $countries = $snapshots->countries();
    expect($countries)->toHaveCount(250);
    $stateCount = 0;
    $cityCount = 0;
    foreach ($countries as $country) {
        $states = $snapshots->states($country['value']);
        $stateCount += count($states);
        foreach ($states as $state) {
            $cities = $snapshots->cities($country['value'], $state['value']);
            $cityCount += count($cities);
        }
    }
    expect($stateCount)->toBeGreaterThan(4900)->and($cityCount)->toBeGreaterThan(140000);
});
