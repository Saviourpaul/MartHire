<?php

use App\Models\NigeriaState;
use Database\Seeders\NigeriaLocationSeeder;

beforeEach(function () {
    $this->seed(NigeriaLocationSeeder::class);
});

it('returns local government areas for the selected state as json', function () {
    $state = NigeriaState::query()
        ->where('name', 'Lagos')
        ->firstOrFail();

    $this->getJson(route('locations.states.local-government-areas', $state))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Agege')
        ->assertJsonFragment([
            'name' => 'Ikeja',
            'slug' => 'ikeja',
        ]);
});
