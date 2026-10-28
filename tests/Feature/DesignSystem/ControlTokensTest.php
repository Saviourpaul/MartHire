<?php

use Illuminate\Support\Facades\Blade;

test('core controls still render with their nw classes', function () {
    $html = Blade::render('<button class="nw-button nw-button--primary">Go</button>'
        .'<input class="nw-input" /><select class="nw-select"><option>x</option></select>');

    expect($html)->toContain('nw-button--primary')->toContain('nw-input')->toContain('nw-select');
});
