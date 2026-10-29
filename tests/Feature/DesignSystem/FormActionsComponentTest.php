<?php

use Illuminate\Support\Facades\Blade;

test('form.actions renders the primary slot and an optional cancel slot', function () {
    $html = Blade::render('<x-form.actions><x-slot:cancel><button>Cancel</button></x-slot:cancel><button>Save</button></x-form.actions>');

    expect($html)->toContain('Save')->toContain('Cancel');
    // cancel renders before the primary action
    expect(strpos($html, 'Cancel'))->toBeLessThan(strpos($html, 'Save'));
});

test('form.actions renders without a cancel slot', function () {
    $html = Blade::render('<x-form.actions><button>Send</button></x-form.actions>');

    expect($html)->toContain('Send');
});
