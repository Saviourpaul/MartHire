<?php

use App\Models\PlatformSetting;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\DB;

test('fresh platform defaults and unpersisted settings use Marthire', function () {
    expect(PlatformSetting::query()->findOrFail(1)->display_name)->toBe('Marthire')
        ->and((new PlatformSetting)->display_name)->toBe('Marthire')
        ->and(app(PlatformSettingsService::class)->presentation()['display_name'])->toBe('Marthire')
        ->and(config('mail.markdown.theme'))->toBe('marthire-theme');
});

test('the branding migration updates stock names once without changing contact ownership', function () {
    DB::table('platform_settings')->where('id', 1)->update([
        'display_name' => 'NearbyWorks', 'mail_from_name' => 'MartHire', 'version' => 7,
        'mail_from_address' => 'hiring@custom.example', 'support_email' => 'support@custom.example',
        'facebook_url' => 'https://www.facebook.com/MartHirerecruit',
    ]);

    $migration = require database_path('migrations/2026_10_31_120000_rebrand_platform_to_marthire.php');
    $migration->up();
    $migration->up();

    $settings = PlatformSetting::query()->findOrFail(1);
    expect($settings->display_name)->toBe('Marthire')
        ->and($settings->mail_from_name)->toBe('Marthire')
        ->and($settings->version)->toBe(8)
        ->and($settings->mail_from_address)->toBe('hiring@custom.example')
        ->and($settings->support_email)->toBe('support@custom.example')
        ->and($settings->facebook_url)->toBe('https://www.facebook.com/MartHirerecruit');
});

test('the branding migration preserves customized platform and sender names', function () {
    DB::table('platform_settings')->where('id', 1)->update([
        'display_name' => 'Acme Hiring', 'mail_from_name' => 'Acme Recruitment Team', 'version' => 7,
    ]);

    $migration = require database_path('migrations/2026_10_31_120000_rebrand_platform_to_marthire.php');
    $migration->up();

    $settings = PlatformSetting::query()->findOrFail(1);
    expect($settings->display_name)->toBe('Acme Hiring')
        ->and($settings->mail_from_name)->toBe('Acme Recruitment Team')
        ->and($settings->version)->toBe(7);
});
