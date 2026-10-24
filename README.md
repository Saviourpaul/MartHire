
## About NearByWorks




## Workable integration

Jobs are ingested from Workable accounts into `job_postings`, one account per company.

**Configuration (non-secret, `.env`)**

- `WORKABLE_API_VERSION`, `WORKABLE_HOST_TEMPLATE`, `WORKABLE_USER_AGENT`
- `WORKABLE_FAKE` - when `true`, the client reads representative fixtures instead of calling Workable (no network).
- `WORKABLE_SYNC_EMPTY_GUARD_MIN_OPEN`, `WORKABLE_SCHEMA_CACHE_MINUTES`

The Workable subdomain and API token are stored per company in the database. They are never read from env.

**Adding a company**

```
php artisan workable:add-company {subdomain} [--name=...] [--apply-mode=redirect]
```

The API token is entered at a hidden (secret) prompt and is never passed as an argument. It is encrypted at rest.

**Syncing**

```
php artisan workable:sync
```

Queues one sync job per active company. The sync is idempotent: re-running with unchanged data creates, updates and closes nothing. Disappeared jobs are closed, and an empty response while many postings are open is treated as suspicious and does not sweep. The command is scheduled every 30 minutes.

**Status**

A real Workable subdomain, token and live-response fixtures are still pending. The W0/W1 work runs on representative fixtures and `WORKABLE_FAKE`.
