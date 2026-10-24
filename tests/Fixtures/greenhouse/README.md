# Greenhouse Job Board fixtures

These are **representative stand-in fixtures**, not captured responses. They match the documented Greenhouse Job Board API shapes (spec §2):

- `jobs.json` — body of `GET /boards/{token}/jobs?content=true`
- `job_with_questions.json` — body of `GET /boards/{token}/jobs/{id}?questions=true`

They exist so the DTO, client and sync tests can run before a real board token is available. Job 1 covers a fully populated job with HTML-entity-encoded `content`; job 2 covers the missing-optional-fields case (null `location` and `requisition_id`, empty arrays).

**Replace these with a live capture once the real board token arrives.** Per spec §0, where this document and a real payload disagree, the real payload wins. Capture with:

```
curl "https://boards-api.greenhouse.io/v1/boards/{token}/jobs?content=true" > jobs.json
curl "https://boards-api.greenhouse.io/v1/boards/{token}/jobs/{id}?questions=true" > job_with_questions.json
```

No real candidate PII is contained in any fixture.
