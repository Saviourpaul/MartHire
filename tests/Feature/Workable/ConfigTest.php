<?php

it('config has expected defaults', function () {
    expect(config('workable.api_version'))->toBe('v3')
        ->and(config('workable.host_template'))->toBe('https://%s.workable.com/spi/%s')
        ->and(config('workable.user_agent'))->toBe('Marthire/1.0 (+ops@marthire.test)')
        ->and(config('workable.fake'))->toBeFalse()
        ->and(config('workable.sync_empty_guard_min_open'))->toBe(5)
        ->and(config('workable.schema_cache_minutes'))->toBe(15)
        ->and(config('workable.application.max_file_mb'))->toBe(5)
        ->and(config('workable.application.payload_retention_days'))->toBe(30);
});
