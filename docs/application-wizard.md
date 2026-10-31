# Application wizards

Two existing application pipelines share the wizard without sharing persistence:

- Workable `Apply.blade.php`: contact details, CV/supporting details, employer
  questions (when present), review/consent. Submission still validates the live
  schema, stores files privately and queues `JobApplication` for the outbox.
- Organization `client/job-application.blade.php`: personal details, address,
  documents, review. Submission still uses `StoreApplicationFormRequest` and
  `ApplicationFormService`, including tenant ownership, private documents and the
  applicant receipt.

`form-wizard.js` owns navigation/review. `application-form.js` reuses the existing
field/file handling and adds step validation and JSON submission. Progress follows
the current step: input events never advance it, and Next moves only after the
active step passes validation. Back/Edit keeps the same mounted inputs, including
selected files. Enter on an earlier step acts as Next. Final submission validates
all steps before sending.

Both controllers preserve normal HTML redirects and add JSON success responses.
The enhanced wizard receives 422 errors without a reload, retains fields/files,
opens the first invalid step and maps canonical Workable keys to visible controls.
An actual page reload cannot restore file inputs; the HTML fallback asks the user
to choose those files again. Personal data is not copied to browser storage.

The shared `location-selector.js` serves native applications and profile editing.
See [location-selection.md](location-selection.md) for the World route collision,
parent-bound validation, manual-entry coverage policy and deployment cache notes.
No location fields are added to Workable when its schema does not collect them.

Shared Blade progress/review/actions components and the existing Tailwind/nw CSS
provide focusable step headings, inline errors, an error summary, live progress,
mobile layouts and reduced-motion styling. Production assets require `npm run build`.

Verification:

```text
node --test tests/js/location-selector.test.mjs tests/js/application-wizard.test.mjs
php artisan test --compact tests/Feature/Locations tests/Feature/Workable tests/Feature/Jobs/NativeApplicationTest.php tests/Feature/ProfileTest.php tests/Feature/Jobs/JobPagesTest.php
```
