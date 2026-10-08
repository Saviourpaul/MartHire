<?php

use App\Models\Job;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// Local browser-check router. Never exposes a login bypass in application routes.
// php -S 127.0.0.1:8127 -t public tests/browser/location-preview.php (APP_ENV=testing)
if (getenv('APP_ENV') !== 'testing') {
    http_response_code(404);
    exit;
}
$root = dirname(__DIR__, 2);
$public = realpath($root . '/public');
$asset = realpath($public . '/' . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
if ($asset && str_starts_with($asset, $public . DIRECTORY_SEPARATOR) && is_file($asset)) {
    return false;
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config([
    'database.default' => 'sqlite',
    'database.connections.sqlite.database' => ':memory:',
    'database.connections.sqlite.url' => null,
    'cache.default' => 'array',
    'session.driver' => 'array',
    'mail.default' => 'array',
    'queue.default' => 'sync',
    'locations.cache.driver' => 'unavailable',
]);
DB::purge('sqlite');
Artisan::call('migrate', ['--force' => true]);
$user = User::factory()->completeApplicantProfile()->create([
    'first_name' => 'Ada',
    'last_name' => 'Lovelace',
    'email' => 'location-preview@example.test',
    'phone' => '+2348012345678',
    'address' => '12 Preview Street',
]);
$user->setAttribute('zipcode', '100001');
$employer = User::factory()->employer()->create();
Job::factory()->approved()->create(['employer_id' => $employer->id, 'title' => 'Location Preview Job']);
Auth::login($user);
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
