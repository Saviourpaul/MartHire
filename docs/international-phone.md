# International phone input

Application and profile phone fields share `components/phone-input.blade.php` and
the `internationalPhone` Alpine component in `international-phone.js`. The picker
shows local SVG flags and calling codes, searches names/ISO codes/calling codes,
and supports keyboard navigation and a native-select fallback. Number formatting
and validation run locally using `libphonenumber-js/max` metadata, loaded only on
pages with a phone field. Phone country
is independent of the person's address country; pasting an international number
can select its detected phone region.

Laravel validates again with `giggsey/libphonenumber-for-php`. Form requests
normalize valid input to E.164. `phone_country` holds the phone's ISO2 region;
`phone_dial_code` and `phone_national_number` store its separate parts. These
parts are derived on the server, including significant leading zeroes, rather
than trusting submitted dialing metadata. Extensions and text mixed into numbers
are rejected. Validation checks numbering-plan validity, not ownership or reachability.

The migration adds nullable parts to `users` and `application_forms`. Existing
phone values are preserved; parts are filled when the number is next validated
and saved. The existing `phone` column remains the canonical value for reports
and integrations. Native application snapshots and the applicant profile retain
the parts. Workable receives canonical `phone`; its split parts stay in the
encrypted application payload's `phone` metadata and are not sent as provider fields.

`nnjeim/world` supplies country display names, with bundled reference names as a
fallback. Phone calling codes and validation come from the phone libraries, since
World's optional phone-code column is not a complete numbering-plan validator.
All supported phone regions have local flags, including additional AC/TA assets
from the same pinned Circle Flags source. No phone number is sent to a third-party
service for formatting or validation.

Deployment: install Composer/npm dependencies from their lock files, run
`php artisan migrate --force`, and build with `npm run build`. `PHONE_DEFAULT_COUNTRY`
defaults to `NG` for a new field or a legacy national number with no region context.

Primary library documentation:

- [libphonenumber-js](https://github.com/catamphetamine/libphonenumber-js)
- [libphonenumber-for-php](https://github.com/giggsey/libphonenumber-for-php)
