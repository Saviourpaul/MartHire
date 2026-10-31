# Live table inventory

The active table views are shared admin user management, organization jobs,
organization candidates, organization team (members and invitations), and the
three role dashboards. Dashboard refresh regions include their statistics and
related lists so refreshed rows cannot sit beside stale counts.

| Region | Existing route | Contents |
| --- | --- | --- |
| `admin-dashboard` | `admin.dashboard` | Four metrics and newest users/jobs/applications tables |
| `organization-dashboard` | `organisations.dashboard` | Organization metrics, recent applications, ranked jobs and permissions |
| `client-dashboard` | `client.dashboard` | Profile completion, paginated applications and suggested jobs |
| `admin-users` | `admin.users.*` index routes | Account categories, filters, sorting, paginated users and management dialogs |
| `organization-jobs` | `organisations.jobs.index` | Counts, job filters, sorting, pagination and row actions |
| `organization-candidates` | `organisations.candidates.index` | Scoped candidates, search, stage/job filters and pagination |
| `organization-team` | `organisations.team.index` | Invitation form, members and invitations with independent pagers and management dialogs |
| `candidate-review` | `organisations.candidates.show` | Authorized pipeline/interview actions and review workspace (event refresh only) |

These regions cover all ten tables on active routes. The candidate review region
also keeps the selected tab and private document when an action refreshes its
server read model.

Applicant applications now paginate ten per page using `applications_page` on
the existing dashboard route; previously only the newest five were accessible.
Pagination links retain the other query parameters. Dashboard data continues to
use the existing role/organization-scoped queries and policies.

Admin overview metrics previously remained cached for five minutes independently
of the recent-record queries. Live reads bypass that metric cache, while existing
chart caches remain unchanged. Organization and applicant dashboard reads are
already uncached.

`/dashboard` is a role redirect. The old `resources/views/dashboard.blade.php`
with its two tables is not attached to a current route. Legacy notification
controllers and application index/documents/notifications methods reference
missing views and have no registered routes; this work does not create new
routes for them.

Focused dashboard regressions cover live fragment responses, fresh metrics,
organization isolation, applicant pagination/query state, authentication,
role authorization, and allowed fragment names.

## Request and synchronization contract

`App\Support\LiveTables` renders the existing authorized Blade view once and
returns only allowlisted `@fragment` regions when `X-Live-Tables` is present.
Normal navigation still returns the complete page. Live responses are private,
uncached JSON containing region HTML, the canonical URL and page title. No new
read routes or duplicate client-side record models are introduced.

`resources/js/live-tables.js` handles declared filters, pagination and links,
reinitializes Alpine components, and preserves focus and query state. Text search
is debounced. Requests are aborted and revision checked so a slow response cannot
overwrite a newer filter. Sorting and filtering remain server-side. The server
clamps an emptied last page to the last available page. Team pagination retains
both pager parameters; applicant pagination uses `applications_page`.

Non-GET forms inside a region submit their existing CSRF token, method override
and selected submit button using `X-Live-Action: 1`. Existing policies, validation,
transactions and concurrency checks remain authoritative. JSON validation errors
are shown inline without clearing entered values; authentication/permission,
network and rate-limit failures are visible. Successful actions show the shared
toast and fetch fresh rows/counts. Exports, downloads, detail/edit navigation and
other normal links retain their existing behavior.

Changes publish topic-only invalidation events through BroadcastChannel (with a
storage-event fallback) to other tabs on the same origin. Normal committed saves
publish the same topics after their redirect. No account or candidate data is
included in these messages. Changes from other sessions are picked up by a fresh
read every 30 seconds while visible and online, or when the tab regains focus.
This is polling, not a WebSocket subscription. Manual Refresh is also available.

Background updates pause while a dialog is open, an input has focus, or there are
unsaved form edits. Explicit refresh/navigation asks before discarding edits.
Submitting locks only that form until its response arrives. A successful action
does not close a dialog containing another edited form. Responses also recheck
for edits made after a request started and defer replacement when necessary.
Committed edit values remain available if the following read fails; only forms
declared `data-live-reset` (new invitations) clear on success. There is no session
or whole-page reload for table interactions.

To add a table, declare a stable region ID and its topics/pages/refresh interval,
wrap it in a matching Blade fragment, allowlist that fragment in the existing GET
controller, and use `LiveTables::mutation` for its existing write endpoints.
Keep headings, counts, matching dialogs and row data in the same region when they
must remain synchronized. Use `data-live-ignore` on forms that need ordinary
navigation and `data-live-link` only on links that return the same region ID.
