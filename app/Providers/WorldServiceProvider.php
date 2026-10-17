<?php

namespace App\Providers;

use Nnjeim\World\Commands\InstallWorldData;
use Nnjeim\World\Commands\RefreshWorldData;
use Nnjeim\World\Commands\UpdateGeoipDatabase;
use Nnjeim\World\Http\Middleware\Geolocate;
use Nnjeim\World\WorldServiceProvider as BaseWorldServiceProvider;

/**
 * Custom nnjeim/world provider.
 *
 * It inherits the package bindings/config from the base provider but overrides
 * boot() so the world reference tables (countries/states/cities/…) are NOT
 * registered as standard application migrations. Those tables are provisioned
 * only by `php artisan world:install` / the WorldSeeder, keeping the
 * application's own schema free of location reference tables.
 *
 * The package is excluded from Laravel package auto-discovery (see composer.json
 * "extra.laravel.dont-discover") so this provider replaces it.
 */
class WorldServiceProvider extends BaseWorldServiceProvider
{
    public function boot(): void
    {
        if (config('world.modules.geolocate', true)) {
            $this->app->make('router')->aliasMiddleware('geolocate', Geolocate::class);
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallWorldData::class,
                RefreshWorldData::class,
                UpdateGeoipDatabase::class,
            ]);
        }
    }
}
