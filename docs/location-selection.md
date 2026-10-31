# Applicant location selection

`LocationController` returns `{data: [{value, label}], meta: {manual: boolean}}`
through the authenticated `locations.states` and `locations.cities` routes.
The route names are the client contract. Application lookup URLs need three
segments to avoid `nnjeim/world`'s `{prefix?}/states` and `{prefix?}/cities` routes,
which otherwise capture the old two-segment application URLs before the intended
controller runs and return a different payload shape.

`LocationService` uses canonical Nigerian states/LGAs for Nigeria and configured
World models for every other country. `WorldLocations` respects each model's
connection and table. State lookup depends on country/state references, without
requiring city references. City options and validation bind both the country and
state, including when different countries contain states with the same name.
Option values remain names, preserving existing applicant/application records.

After deploying these route changes, regenerate any route cache (`php artisan
route:cache`) so lookup URLs use the noncolliding paths. No reference rows need
to be reseeded or edited.

Some valid World parents have no child records. When a known country has no
reference states, `meta.manual` authorizes a typed state. When a valid selected
country/state has no reference cities, it authorizes a typed city. Both decisions
are checked again against live reference records during server validation. An
unavailable connection, missing table, or incomplete schema returns HTTP 503 and
never enables manual entry. Unknown parents and malformed requests return 422.
Nigerian states and LGAs always use their canonical options.

The shared selector should reset descendants when a parent changes, preserve
restored selections during initialization, ignore stale/aborted responses, and
offer retry feedback on failed requests. A request failure must remain distinct
from an authorized manual-entry response.

Location options use the configured cache store by default; `LOCATION_CACHE_STORE`
can select another store and `LOCATION_CACHE_TTL` defaults to 3600 seconds. The
versioned key includes the World model/connection/table configuration. Empty
results and failures are not cached, so initially absent reference rows do not
remain an empty selector after installation. Server validation bypasses the
option cache. No application database migration is needed.
