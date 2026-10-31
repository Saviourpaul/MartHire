# Marthire branding and How It Works

The canonical product name is **Marthire**. Public/account/auth copy, metadata,
mail templates/defaults, CSV exports and the JavaScript notification API use it.
The existing Marthire logo artwork is retained. Technical CSS names (`nw-*`) and
existing database/workspace names do not represent public branding and are not
renamed.

Static assets now live under `public/asset/marthire/`; Vite output and PDF support
assets live under its `build/` subdirectory. Deploy the entire directory along
with the new views and source changes. Both documented build commands use the
same Vite configuration. No runtime image requests go to the reference site.

For deployment, run `npm run build`, `php artisan migrate --force`, then refresh
Laravel's config/view caches as appropriate for the environment. The branding
migration changes only stock legacy display/sender labels and the display-name
schema default. Custom branding, contact addresses, verified social URLs,
credentials and existing recruitment records are preserved. It is idempotent
and does not attempt to overwrite later administrator edits on rollback.

Old-name strings remain only where needed to recognize legacy settings and
migrate a saved appearance preference. `marthire-theme` is the current preference
key; `window.Marthire` provides shared notifications/confirmations, and live
tables use Marthire-named synchronization channels. The mail CSS theme is
`marthire-theme`.

`/how-it-works` is a public named route linked in the desktop/mobile header,
footer and home page. Its hero, three alternating feature panels, four-step
timeline and final contact section follow the requested reference structure.
Copy describes the implemented workflow, including completed interviews and
explicit selection; it does not claim a separate offers/onboarding module.
Buttons link to real section anchors, contact and public job routes.

The seven original illustrations and decorative backgrounds have local source
records in `public/asset/marthire/how-it-works/SOURCES.md`. They are identified as
illustrations and do not represent real applicant records or platform metrics.
The optional large grain texture is omitted. Phone layouts use one column and
a left-side timeline; desktop layouts alternate the image and copy. Existing
theme tokens and reduced-motion behavior remain in use.
