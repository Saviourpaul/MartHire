<?php

it('config has expected defaults', function () {
    expect(config('greenhouse.base_url'))->toBe('https://boards-api.greenhouse.io/v1')
        ->and(config('greenhouse.sync_empty_guard_min_open'))->toBe(5)
        ->and(config('greenhouse.schema_cache_minutes'))->toBe(15)
        ->and(config('greenhouse.fake'))->toBeFalse()
        ->and(config('greenhouse.application.max_file_mb'))->toBe(5)
        ->and(config('greenhouse.application.payload_retention_days'))->toBe(30);
});
