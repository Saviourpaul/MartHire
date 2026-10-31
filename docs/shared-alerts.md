# Shared alerts and workflow safeguards

All three layouts render the same bottom-right toast region and native HTML
dialog styled with the existing design tokens. Server success/error/warning/info
flash messages are preserved. JavaScript behavior lives in `resources/js/alerts.js`;
dialog styles live in `resources/css/alerts.css`.

```js
window.Marthire.notify('Saved successfully.', { type: 'success' });
window.Marthire.notify('Please check the details.', { type: 'warning' });
await window.Marthire.alert('The action could not be completed.', { type: 'error' });
const approved = await window.Marthire.confirm('Continue?', {
    title: 'Confirm action', type: 'warning', confirmLabel: 'Continue',
});
```

`success`, `error`, `warning`, and `info` are supported. Error/warning toasts remain
until dismissed by default. Timed messages pause while hovered or focused.
`nw:notify` custom events accept the same message/type/duration data. Content is
inserted as text, never HTML. Dialogs queue, focus Cancel initially for
confirmations, trap keyboard focus, support Escape, and restore the trigger.

Normal Laravel forms use `data-confirm="Message"`, with optional
`data-confirm-title`, `data-confirm-label`, and `data-confirm-type`. Attributes on
the clicked submit button override the form, so different pipeline actions can
share a form. `data-confirm-field="selection_confirmed"` creates a hidden accepted
value only after confirmation. The handler uses `requestSubmit(originalSubmitter)`
to preserve action name/value, native validation, method overrides and CSRF.
Cancellation adds no accepted value and makes no request. Pending and repeated
native submissions are guarded; browser back/forward restoration resets the
submission guard. AJAX forms should await the API before their fetch call.

The resource audit found native browser confirmations in candidate, job, team
and account actions; these now use the shared dialog. No native `window.alert`
or `window.confirm` call remains in first-party resources. CLI confirmations and
server-side security confirmation services are separate existing mechanisms.

Candidate country display uses `CountryDisplayService` and `x-country-display`.
Installed World names take precedence; bundled package names and existing ISO
aliases support legacy records. Local lowercase SVG paths come from the shared
flag service. Unknown or missing assets show a globe and escaped country text.
Legacy snapshot names are resolved before using live-profile fallback values.
Presentation does not expand selectable countries or modify saved records.

Application document labels come from `ApplicationDocument::displayLabel()`:
submitted enum types such as Résumé / CV, Education certificate, and specific
identity documents. Organization review data excludes original filenames and
custom document names; activity and applicant receipt use the same labels.
Original filenames remain stored and are retained in authorized download headers.
