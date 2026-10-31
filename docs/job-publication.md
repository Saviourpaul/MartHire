# Public job publication and country data

Organization jobs require a `country_code` selected from the configured
`nnjeim/world` country model. The job stores the ISO 3166-1 alpha-2 code;
country names, coordinates, and flag presentation come from `JobCountryService`.
World's configured model, table name, and connection are respected.
Country is the only job location input. New jobs store no separate location or
country-name copy; old location values remain in the database and are not
overwritten when a country is selected during editing.

## Deployment

Run the standard application migrations (`php artisan migrate --force`) and
build the frontend (`npm run build`). World reference countries must already be
installed using the project's existing World setup. An unavailable country
dataset leaves the job selector unavailable and country validation fails closed.

The country migration preserves existing jobs with a nullable country code.
Organizations must select a country when editing those jobs; the migration does
not assign a country from free-text locations. Public presentation can still
recognize legacy locations using the existing map lookup.

## Public destinations

- `/` and `/jobs` combine published, unexpired organization jobs and open Workable
  postings. `/workable-jobs` remains a compatible URL for the same public board.
- `/jobs/{slug}` displays an eligible organization job.
- `/jobs/{slug}/apply` requires an active, verified applicant account. Submission
  uses the existing candidate pipeline and private application-document storage.
- `/client/applications/{application}` is the applicant's authorized receipt.
- Workable detail, hosted application destinations, and native Workable submission
  routes remain separate from organization job applications.

`PublicJobService` provides source-specific URLs and namespaced native IDs so an
imported shortcode cannot collide with a native job. Public native queries bypass
organization scoping only with explicit publication and deadline restrictions.
Draft, suspended, closed, expired, and undated native jobs are unavailable through
public detail and application endpoints.

The existing map groups both sources by country. A selected country takes priority
over free-text city matching. Optional World coordinate columns fall back to the
package's bundled coordinate metadata for installed countries. All supported World
countries have local SVG flags under `public/asset/marthire/flags/{iso2}.svg`,
using lowercase ISO2 filenames. Flag URLs use Laravel's asset URL generator and
have no external flag-service dependency. The country asset coverage test catches
missing or invalid SVGs when the World reference dataset changes.

Uploaded job logos use the public filesystem disk. Ensure `public/storage` is
linked to that disk (`php artisan storage:link` on a new deployment). Public job
cards and details use the resolved logo URL; absent or unavailable logos fall back
to the company's initials. Editing a job previews the saved logo until a new file
is selected.

## Verification

`php artisan test` uses isolated SQLite and in-memory rate-limit storage. Country
tests install minimal World reference fixtures on the test connection.

The job feature tests cover country validation/storage/editing, publication through
guest discovery, flags and map markers, separate source URLs, tenant isolation,
privacy/deadline restrictions, actual native application submission, private files,
duplicate submissions, and applicant receipt authorization.
