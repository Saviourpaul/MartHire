<?php

use Illuminate\Support\Facades\Blade;

// The `tabs` slot in an isolated Blade::render trips PHPUnit's "did not close its own
// output buffers" risky check (a test-harness quirk with nested slots, not a component
// fault). The tabs region is instead proven in real views by Task 6's
// UserManagementTest / CandidateManagementTest, which render the segmented tabs through
// this component. The default/filters/actions regions render cleanly and are unit-tested here.

test('table.toolbar renders the default search slot inside a toolbar', function () {
    $html = Blade::render('<x-table.toolbar>SEARCHONLY</x-table.toolbar>');

    expect($html)->toContain('SEARCHONLY')->toContain('nw-toolbar');
});

test('table.toolbar renders the filters slot', function () {
    $html = Blade::render('<x-table.toolbar>S<x-slot:filters>FILTERS</x-slot:filters></x-table.toolbar>');

    expect($html)->toContain('FILTERS');
});

test('table.toolbar renders the actions slot in a right-aligned region', function () {
    $html = Blade::render('<x-table.toolbar>S<x-slot:actions>ACTIONS</x-slot:actions></x-table.toolbar>');

    expect($html)->toContain('ACTIONS')->toContain('nw-toolbar__actions');
});
