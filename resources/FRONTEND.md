# Marthire frontend

Public, authentication and account views share the design tokens in
`css/app.css`, Alpine interactions in `js/app.js` and Blade components in
`views/components/`. The `nw-*` CSS names are internal design-system identifiers.

Run `npm run build` from the repository root. Vite publishes its manifest, bundles
and version-matched PDF support assets to `public/asset/marthire/build/`.
`node resources/build-frontend.mjs` delegates to the same configuration. The
shared head reads that manifest. Only the build subdirectory is cleared, so local
flags, reference illustrations and photography in the parent directory survive.

## Public pages

`PublicJobService` combines eligible organization jobs and imported Workable
listings. `JobCatalog` prepares the public cards and map data. Country names and
flags come from the existing World country integration and local
`public/asset/marthire/flags/{code}.svg` files. No sample job feed is required.

`/how-it-works` is a public Blade page linked from the header, mobile menu, footer
and home page. Its three feature panels and four-stage timeline use
`css/how-it-works.css`; original reference illustrations and source attribution
are in `public/asset/marthire/how-it-works/`. Illustrations are not live platform
data. All actions use existing routes or section anchors.

## Account workflows

`/dashboard` redirects to the user's role dashboard. Organization, applicant and
admin dashboards use their existing authorized read models. Active table views
use the shared JavaScript/Blade fragment layer described in
`docs/live-table-inventory.md`. Candidate documents remain private and render in
the review workspace; deployment must include the complete PDF build assets.

Application wizards, phone inputs, avatar uploads and country/state/city selectors
have separate JavaScript modules. Existing backend requests and policies remain
authoritative. See the focused workflow documentation in `docs/`.

Appearance preferences use `marthire-theme`; the shared head migrates an existing
legacy preference once. Toasts and confirmation dialogs use `window.Marthire`.
The mail theme is `views/vendor/mail/html/themes/marthire-theme.css`.

Photography, flags and independent resource-logo provenance is recorded in
`public/asset/marthire/SOURCES.md`. Resource logos do not imply endorsement.
