<?php

use Illuminate\Support\Facades\Http;

it('returns canonical country-scoped state and city options without lookup tables', function () {
    config(['locations.cache.driver' => 'unavailable']);
    Http::preventStrayRequests();
    $this->getJson(route('locations.states', ['country' => 'NG']))
        ->assertOk()
        ->assertJsonFragment(['value' => 'Lagos', 'label' => 'Lagos']);
    $this->getJson(route('locations.cities', ['country' => 'NG', 'state' => 'Lagos']))
        ->assertOk()->assertJsonFragment(['value' => 'Ikeja', 'label' => 'Ikeja']);
    $this->getJson('/locations/states/1/local-government-areas')->assertNotFound();
});
