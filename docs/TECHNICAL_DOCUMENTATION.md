# MartHire Technical Documentation

Last reviewed: 2026-07-21

This document describes the current MartHire implementation in this repository. It is intended for developers who need to install, run, maintain, secure, test, and scale the application in local and production environments.

## Project Overview And Architecture

MartHire is a Laravel-based recruitment portal that connects applicants, employers, and administrators.

Primary capabilities:

- Public visitors can browse approved jobs, view job details, and submit contact inquiries.
- Applicants can register, complete profile information, apply for approved jobs, upload identity and education documents, track application statuses, and receive database notifications.
- Employers can create and manage their job posts, review applications for their own jobs, approve or reject candidate applications, and review uploaded documents.
- Administrators can manage users, suspend or activate accounts, review employer job posts, and monitor platform metrics.

Architectural style:

- Laravel 12 monolith using MVC, Blade views, Eloquent models, Form Requests, middleware, services, notifications, mailables, migrations, seeders, and feature tests.
- Server-rendered UI with Blade, Bootstrap/static theme assets, Tailwind/Vite entry points, Alpine.js, and Axios.
- Page/form-driven HTTP interface. There is currently no versioned JSON API under `routes/api.php`.
- Service classes contain the higher-risk workflows, especially dashboards, application submission, review transitions, document storage, and document authorization.
- Database-backed sessions, cache, and queues are the default local configuration.

## Technology Stack And Dependencies

Runtime:

| Area | Current implementation |
| --- | --- |
| Backend | PHP 8.2+, Laravel Framework 12.61 |
| Authentication | Laravel Breeze-style session authentication |
| Templates | Blade |
| ORM/database | Eloquent with migrations; SQLite default in `.env.example`, compatible with MySQL/PostgreSQL conventions used by query helpers |
| Queue | Laravel queue, default `database` connection |
| Cache/session | Laravel cache/session, default database drivers |
| Storage | `local` private disk for application documents, `public` disk for job logos and profile images |
| Mail | Laravel Mail, default `log` mailer locally |
| Frontend build | Vite 7, Laravel Vite plugin |
| Frontend libraries | Tailwind CSS 3, Alpine.js, Axios, Bootstrap/static assets, DataTables/static assets, Select2/static assets |
| Testing | Pest 3 with Pest Laravel plugin |
| Formatting/dev tools | Laravel Pint, Laravel Pail, Laravel Sail, Laravel Boost |
| Monitoring package | Laravel Nightwatch is installed but not enabled in `boost.json` |

Direct PHP packages observed locally:

- `laravel/framework`
- `laravel/breeze`
- `laravel/nightwatch`
- `laravel/tinker`
- `symfony/http-client`
- `pestphp/pest`
- `pestphp/pest-plugin-laravel`
- `laravel/pint`
- `laravel/pail`
- `laravel/sail`
- `laravel/boost`
- `fakerphp/faker`
- `mockery/mockery`
- `nunomaduro/collision`

Direct Node packages observed locally:

- `vite`
- `laravel-vite-plugin`
- `tailwindcss`
- `@tailwindcss/forms`
- `@tailwindcss/vite`
- `alpinejs`
- `axios`
- `autoprefixer`
- `postcss`
- `concurrently`

## Folder And File Structure

Important paths:

```text
app/
  Console/Commands/                 Maintenance commands for default users and document migrations.
  Enums/                            Role, status, dashboard-period, and document-type enums.
  Http/Controllers/                 Public, applicant, employer, admin, auth, profile, and document controllers.
  Http/Middleware/                  Role and active-account middleware.
  Http/Requests/                    Form-request validation and authorization.
  Mail/                             Welcome email mailable.
  Models/                           Eloquent models and relationships.
  Notifications/                    Database notifications for application/document status changes.
  Providers/AppServiceProvider.php  Rate limiter definitions.
  Services/                         Dashboard, application form, and document file services.
  Support/                          Dashboard date-range helper.

bootstrap/
  app.php                           Laravel 12 app bootstrap, route registration, middleware aliases, custom 404 rendering.

config/
  app.php, auth.php, cache.php,
  database.php, filesystems.php,
  logging.php, mail.php,
  queue.php, session.php            Runtime configuration.

database/
  factories/                        Factories for users, jobs, applications, and documents.
  migrations/                       Schema definition and schema evolution.
  seeders/                          Default users, jobs, application samples, and Nigeria locations.

docs/
  TECHNICAL_DOCUMENTATION.md        This document.

public/
  index.php                         Front controller.
  assets/                           Public theme assets for applicant/public pages.
  admin/assets/                     Public theme assets for admin pages.
  storage -> storage/app/public     Created by `php artisan storage:link`.

resources/
  css/app.css                       Tailwind entry.
  js/app.js                         Alpine/bootstrap entry.
  views/                            Blade views grouped by public, auth, client, employer, admin, profile, components, emails.

routes/
  web.php                           All application web routes.
  auth.php                          Breeze-style auth routes.
  console.php                       Closure console command definitions.

tests/
  Feature/                          End-to-end feature coverage for auth, RBAC, dashboards, jobs, applications, documents.
  Unit/                             Unit tests.

composer.json                       PHP dependencies and Composer scripts.
package.json                        Node dependencies and Vite scripts.
phpunit.xml                         Test environment configuration.
vite.config.js                      Vite/Laravel asset pipeline.
tailwind.config.js                  Tailwind content paths and theme extension.
```

## System Design And Application Flow

Public discovery flow:

1. `GET /` loads the home page with the latest 3 approved jobs.
2. `GET /find-jobs` lists approved jobs with search and category filters, paginated at 12 per page.
3. `GET /job-details/{job}` shows approved jobs publicly. Pending or rejected jobs are only visible to admins or the owning employer.
4. `GET /jobs/{job}/apply` starts an application. Guests are redirected to registration and the intended URL is saved.

Authentication flow:

1. Guests register through `POST /register`.
2. Passwords must meet strong password rules during public registration.
3. The new user is created as an active applicant, a queued welcome email is created, the user is logged in, and the browser redirects to the intended URL or dashboard.
4. Login uses `LoginRequest`, rate limits by email/IP, blocks suspended users, regenerates the session, and updates `last_login_at`.
5. Logout invalidates the session and regenerates the CSRF token.

Applicant application flow:

1. Applicant opens an approved job application form.
2. `StoreApplicationFormRequest` validates personal details, state/LGA consistency, NIN/BVN numbers, identity documents, and 1 to 10 education documents.
3. `ApplicationFormService::submit()` prevents duplicate applications for the same job/user pair.
4. The service creates the application inside a database transaction.
5. Profile image uploads are stored on the `public` disk under `profile-images/`.
6. Application documents are stored on the private `local` disk under `application-documents/{application_id}/`.
7. NIN/BVN document numbers are encrypted through the `ApplicationDocument` model mutator.
8. Applicant profile fields are synchronized from the submitted application.
9. A pending application status history row is recorded.

Employer review flow:

1. Employers manage their job posts under the authenticated employer routes.
2. New or edited jobs are reset to `pending` so an admin can review them.
3. Employers can view only applications for jobs they own.
4. Employers can change application status to `pending`, `approved`, or `rejected` with optional remarks.
5. Employers can independently review each uploaded application document with optional remarks.
6. Review changes create status-history rows and database notifications for applicants when the status changes.

Admin flow:

1. Admins can browse and filter users by role, status, date, email, phone, and search terms.
2. Admins can create users, update users, suspend/activate users, soft-delete users, and send password reset links.
3. Admins cannot suspend, demote, or delete their own active administrator account, and cannot remove the final active admin.
4. Admins can browse all jobs, filter/sort them, view full job details, and mark jobs pending/approved/rejected.
5. Admin dashboard metrics are generated from live user, job, and application data over preset or custom date ranges.

Document access flow:

1. Document preview/download routes require `auth` and `active.account`.
2. `ApplicationDocumentFileService` authorizes access for the owning applicant, the owning employer, or an admin.
3. Preview supports PDF, JPEG, and PNG MIME types only.
4. Downloads are available for authorized users regardless of preview support.
5. Responses include private/no-store cache headers and `X-Content-Type-Options: nosniff`.

## Core Domain Model

User roles:

- `admin`
- `employer`
- `applicant`

User statuses:

- `active`
- `suspended`

Job statuses:

- `pending`
- `approved`
- `rejected`

Application statuses:

- `pending`
- `approved`
- `rejected`

Application document types:

- `nin` - National Identity Number
- `bvn` - Bank Verification Number
- `education` - Educational Qualification

Main model relationships:

- `User` has many `Job` records as `employer_id`.
- `User` has many `ApplicationForm` records as applicant.
- `Job` belongs to an employer user and has many applications.
- `ApplicationForm` belongs to a job, belongs to an applicant, optionally belongs to a reviewer, has many documents, and has many application status histories.
- `ApplicationDocument` belongs to an application form, optionally belongs to a reviewer, and has many document status histories.
- `NigeriaState` has many local government areas.
- `NigeriaLocalGovernmentArea` belongs to a state.

## Database Schema And Relationships

The schema is defined by migrations in `database/migrations`. The current migrated database includes the tables below.

### `users`

Purpose: authenticatable accounts for admins, employers, and applicants.

Key columns:

- `id`
- `first_name`, `last_name`, `username`
- `email`, unique
- `date_of_birth`, nullable
- `email_verified_at`, nullable
- `password`, hashed
- `role`, indexed
- `status`, indexed
- `approved_at`, `suspended_at`, `last_login_at`, nullable
- `profile_image_path`, `phone`, `address`, `nationality`, `state_of_origin`, `local_government_area`, `zipcode`, nullable
- `remember_token`
- `created_at`, `updated_at`, `deleted_at`

Important behavior:

- The model casts `password` as `hashed`.
- Users use soft deletes.
- The model default role is applicant and model default status is active.
- The RBAC migration has a legacy database default of `pending`; create users through application code or set `status` explicitly until the database default is normalized in a future migration.

### `job_posts`

Purpose: employer-created job listings.

Key columns:

- `id`
- `employer_id`, foreign key to `users.id`, cascade delete
- `title`, `description`, `company`, `category`
- `logo`, nullable
- `start_date`, `due_date`
- `status`, one of `pending`, `approved`, `rejected`
- `created_at`, `updated_at`

Indexes:

- `employer_id`, `status`
- `status`, `created_at`
- `employer_id`, `created_at`
- `category`, `created_at`

### `application_forms`

Purpose: submitted applicant applications for jobs.

Key columns:

- `id`
- `job_id`, foreign key to `job_posts.id`, cascade delete
- `user_id`, foreign key to `users.id`, cascade delete
- `reference`, unique, format like `APP-YYYYMMDD-ABC123`
- `status`, one of `pending`, `approved`, `rejected`
- `submitted_at`
- Applicant snapshot fields: `first_name`, `middle_name`, `last_name`, `email`, `phone`, `nationality`, `date_of_birth`, `gender`, `marital_status`, `state_of_origin`, `local_government_area`, `address`, `zipcode`, `profile_image_path`
- Review fields: `reviewed_by`, `reviewed_at`, `employer_remarks`
- `created_at`, `updated_at`

Constraints and indexes:

- Unique `job_id`, `user_id` prevents duplicate applications for the same job and applicant.
- Indexes on `job_id/status`, `user_id/status`, `submitted_at`, `user_id/submitted_at`, `job_id/submitted_at`, `status/submitted_at`.

### `application_documents`

Purpose: private uploaded documents attached to applications.

Key columns:

- `id`
- `application_form_id`, foreign key to `application_forms.id`, cascade delete
- `document_type`, one of `nin`, `bvn`, `education`
- `document_name`
- `document_number`, nullable and encrypted by the model mutator for NIN/BVN values
- `file_path`
- `original_name`
- `mime_type`
- `size`
- `status`, one of `pending`, `approved`, `rejected`
- `reviewed_by`, `reviewed_at`, `employer_remarks`
- `created_at`, `updated_at`

Indexes:

- `application_form_id`
- `application_form_id`, `document_type`
- `status`, `document_type`

Implementation note:

- The original schema enforced one document per application/document type. The migration `2026_06_18_130200_allow_multiple_education_documents.php` removes that uniqueness and replaces it with indexes, allowing multiple education documents.

### `application_status_histories`

Purpose: audit trail for application status changes.

Key columns:

- `id`
- `application_form_id`, foreign key to `application_forms.id`, cascade delete
- `from_status`, nullable
- `to_status`
- `changed_by`, nullable foreign key to `users.id`, null on delete
- `remarks`, nullable
- `created_at`

Index:

- `application_form_id`, `created_at`

### `application_document_status_histories`

Purpose: audit trail for document status changes.

Key columns:

- `id`
- `application_document_id`, foreign key to `application_documents.id`, cascade delete
- `from_status`, nullable
- `to_status`
- `changed_by`, nullable foreign key to `users.id`, null on delete
- `remarks`, nullable
- `created_at`

Index:

- `application_document_id`, `created_at`

### `states`

Purpose: normalized Nigerian states and the FCT for applicant profile/application forms.

Key columns:

- `id`
- `name`, unique
- `slug`, unique
- `type`, default `state`
- `sort_order`
- `created_at`, `updated_at`

### `local_government_areas`

Purpose: local government areas and FCT area councils.

Key columns:

- `id`
- `nigeria_state_id`, foreign key to `nigeria_states.id`, cascade delete
- `name`
- `slug`
- `sort_order`
- `created_at`, `updated_at`

Constraints and indexes:

- Unique `nigeria_state_id`, `slug`
- Index `nigeria_state_id`, `name`

### Framework Tables

Laravel framework tables are also present:

- `password_reset_tokens`
- `sessions`
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `notifications`

## Authentication And Authorization

Authentication:

- Session-based web authentication using Laravel's `web` guard.
- Auth routes are defined in `routes/auth.php`.
- Public registration creates active applicant users.
- Login checks credentials, applies a 5-attempt throttle by email/IP, rejects suspended users, regenerates sessions, and updates `last_login_at`.
- Password reset is available through Laravel password broker routes.
- Password update requires the current password.
- Account deletion requires current password and performs a soft delete.

Authorization:

- `role` middleware is registered in `bootstrap/app.php` and implemented by `EnsureUserHasRole`.
- `active.account` middleware is registered in `bootstrap/app.php` and implemented by `EnsureAccountIsActive`.
- Applicants can access applicant dashboards, own applications, own documents, and public approved jobs.
- Employers can manage only their own jobs and applications for their own jobs.
- Admins can access admin user/job management and all document downloads/previews.
- Employers and admins can view active applicant read-only profiles; employers cannot view suspended applicant profiles.
- Review Form Requests enforce employer ownership before an application or document status can be changed.

Recommended authorization conventions for future work:

- Keep route middleware as the first layer of protection.
- Keep ownership checks in Form Requests, policies, or services for write operations.
- Prefer Laravel policies when authorization rules begin to repeat across controllers.
- Add tests for every new route that proves unauthorized roles receive `403`, not just hidden navigation.

## API Endpoints And Request/Response Examples

This project currently exposes web routes, not a JSON REST API. Examples below describe the HTTP contracts used by browser forms and Blade pages. Mutating requests require a valid Laravel session and CSRF token.

### Public Pages

| Method | Path | Name | Behavior |
| --- | --- | --- | --- |
| GET | `/` | `home` | Home page with latest approved jobs |
| GET | `/find-jobs` | `jobs.public` | Public job list, search/category filters, 12 per page |
| GET | `/job-details/{job}` | `job-details` | Job details; unapproved jobs hidden from public |
| GET | `/about` | `about` | About page |
| GET | `/services` | `services` | Services page |
| GET | `/features` | `features` | Features page |
| GET | `/faq` | `faq` | FAQ page |
| GET | `/contact` | `contact` | Contact form |
| POST | `/contact` | `contact.store` | Validates contact inquiry and redirects with success flash |

Public job search example:

```http
GET /find-jobs?search=engineer&category=Technology HTTP/1.1
Accept: text/html
```

Typical response:

```http
HTTP/1.1 200 OK
Content-Type: text/html
```

Contact form example:

```http
POST /contact HTTP/1.1
Content-Type: application/x-www-form-urlencoded

_token=csrf-token
&name=Ada+Okafor
&email=ada@example.com
&phone=08012345678
&subject=Employer+partnership
&inquiry_type=employer
&message=We+would+like+to+post+roles+on+MartHire.
```

Successful response:

```http
HTTP/1.1 302 Found
Location: /contact
```

Validation failure redirects back with errors in the session.

### Auth Routes

| Method | Path | Behavior |
| --- | --- | --- |
| GET | `/register` | Registration form |
| POST | `/register` | Creates active applicant, queues welcome email, logs user in |
| GET | `/login` | Login form |
| POST | `/login` | Authenticates, regenerates session, updates `last_login_at` |
| POST | `/logout` | Logs out, invalidates session |
| GET/POST | `/forgot-password` | Password reset request UI/submission |
| GET | `/reset-password/{token}` | Password reset form |
| POST | `/reset-password` | Resets password |
| GET/POST | `/confirm-password` | Password confirmation |
| PUT | `/password` | Updates authenticated user's password |

Registration example:

```http
POST /register HTTP/1.1
Content-Type: application/x-www-form-urlencoded

_token=csrf-token
&first_name=Ada
&last_name=Okafor
&username=ada
&email=ada@example.com
&password=StrongPass!123
&password_confirmation=StrongPass!123
```

Successful response:

```http
HTTP/1.1 302 Found
Location: /Dashboard
```

### Applicant Routes

| Method | Path | Middleware | Behavior |
| --- | --- | --- | --- |
| GET | `/jobs/{job}/apply` | `active.account` | Shows application wizard or redirects guests to registration |
| POST | `/jobs/{job}/apply` | `auth`, `active.account`, `role:applicant`, `throttle:application-submit` | Stores application and uploads |
| GET | `/Client/Application` | `auth`, `active.account`, `role:applicant` | Applicant applications list |
| GET | `/client/jobs` | `auth`, `active.account`, `role:applicant` | Same application listing view |
| GET | `/client/applications/{applicationForm}` | `auth`, `active.account`, `role:applicant` | Applicant application details, owner only |
| GET | `/client/documents` | `auth`, `active.account`, `role:applicant` | Applicant uploaded documents, 10 per page |
| GET | `/client/notifications` | `auth`, `active.account`, `role:applicant` | Applicant notifications, 10 per page |
| GET | `/client/settings` | `auth`, `active.account`, `role:applicant` | Applicant settings page |

Application submission example:

```http
POST /jobs/42/apply HTTP/1.1
Content-Type: multipart/form-data

_token=csrf-token
first_name=Ada
middle_name=
last_name=Okafor
email=ada@example.com
phone=08012345678
nationality=Nigeria
date_of_birth=1995-04-12
gender=female
marital_status=single
state_of_origin=Lagos
local_government_area=Ikeja
address=12 Example Street
zipcode=100001
profile_image=@avatar.jpg
nin_number=12345678901
nin_document=@nin.pdf
bvn_number=10987654321
bvn_document=@bvn.pdf
education_documents[0][type]=bsc
education_documents[0][file]=@degree.pdf
education_documents[1][type]=nysc
education_documents[1][file]=@nysc.pdf
```

Successful response:

```http
HTTP/1.1 302 Found
Location: /client/applications/{application_id}
```

Common failure responses:

- `403` if the user is not an applicant or tries to view another applicant's application.
- `404` if the job is not approved for public application.
- `302` back with validation errors for invalid files, invalid NIN/BVN length, missing profile fields, invalid state/LGA pair, or duplicate applications.

### Profile And Document Routes

| Method | Path | Middleware | Behavior |
| --- | --- | --- | --- |
| GET | `/profile` | `auth`, `active.account` | Edit authenticated user's profile |
| PATCH | `/profile` | `auth`, `active.account`, `throttle:uploads` | Update profile and optional profile image |
| DELETE | `/profile` | `auth`, `active.account` | Soft-delete account after password confirmation |
| GET | `/applicants/{user}/profile` | `auth`, `active.account`, `role:admin,employer` | Read-only applicant profile |
| GET | `/application-documents/{applicationDocument}/preview` | `auth`, `active.account`, `throttle:downloads` | Inline stream for PDF/JPEG/PNG if authorized |
| GET | `/application-documents/{applicationDocument}/download` | `auth`, `active.account`, `throttle:downloads` | Download stream if authorized |

Document preview response example:

```http
HTTP/1.1 200 OK
Content-Type: application/pdf
Cache-Control: private, no-store
X-Content-Type-Options: nosniff
Content-Disposition: inline; filename="nin.pdf"
```

Unsupported preview MIME types return `415`; downloads remain available to authorized users.

### Employer Routes

| Method | Path | Middleware | Behavior |
| --- | --- | --- | --- |
| GET | `/jobs` | `auth`, `active.account`, `role:employer` | Employer job table, searchable/sortable, 15 per page |
| POST | `/jobs` | `auth`, `active.account`, `role:employer`, `throttle:uploads` | Create pending job |
| PUT | `/jobs/{job}` | `auth`, `active.account`, `role:employer`, `throttle:uploads` | Update owned job and reset to pending |
| DELETE | `/jobs/{job}` | `auth`, `active.account`, `role:employer` | Delete owned job and stored logo |
| GET | `/employer/Applied-Candidates` | `auth`, `active.account`, `role:employer` | All applications for owned jobs |
| GET | `/employer/Approved-Candidates` | `auth`, `active.account`, `role:employer` | Approved applications |
| GET | `/employer/Rejected-Candidate` | `auth`, `active.account`, `role:employer` | Rejected applications |
| GET | `/employer/applications/{applicationForm}` | `auth`, `active.account`, `role:employer` | Application review page, owned job only |
| PATCH | `/employer/applications/{applicationForm}/status` | `auth`, `active.account`, `role:employer` | Update application status |
| PATCH | `/employer/application-documents/{applicationDocument}/status` | `auth`, `active.account`, `role:employer` | Update document status |

Create job example:

```http
POST /jobs HTTP/1.1
Content-Type: multipart/form-data

_token=csrf-token
title=Senior Laravel Developer
description=Build and maintain MartHire services.
company=MartHire
category=Technology
logo=@logo.png
start_date=2026-08-01
due_date=2026-08-31
```

Successful response:

```http
HTTP/1.1 302 Found
Location: /jobs
```

Review application example:

```http
PATCH /employer/applications/77/status HTTP/1.1
Content-Type: application/x-www-form-urlencoded

_token=csrf-token
status=approved
remarks=Candidate+meets+the+requirements.
```

Successful response redirects back with a success flash and creates an application status history record.

### Admin Routes

| Method | Path | Middleware | Behavior |
| --- | --- | --- | --- |
| GET | `/Employers` | `auth`, `active.account`, `role:admin` | Employer management table |
| GET | `/administrators` | `auth`, `active.account`, `role:admin` | Administrator management table |
| GET | `/applicants` | `auth`, `active.account`, `role:admin` | Applicant management table |
| GET | `/suspended-accounts` | `auth`, `active.account`, `role:admin` | Suspended account table |
| POST | `/admin/users` | `auth`, `active.account`, `role:admin` | Create user |
| PUT | `/admin/users/{user}` | `auth`, `active.account`, `role:admin` | Update user |
| PATCH | `/admin/users/{user}/suspend` | `auth`, `active.account`, `role:admin` | Suspend user |
| PATCH | `/admin/users/{user}/activate` | `auth`, `active.account`, `role:admin` | Activate user |
| DELETE | `/admin/users/{user}` | `auth`, `active.account`, `role:admin` | Soft-delete user |
| POST | `/admin/users/{user}/password-reset` | `auth`, `active.account`, `role:admin` | Send reset link |
| GET | `/admin/jobs` | `auth`, `active.account`, `role:admin` | All jobs, searchable/filterable/sortable |
| GET | `/admin/jobs/{job}` | `auth`, `active.account`, `role:admin` | Admin job details |
| PATCH | `/admin/jobs/{job}/status` | `auth`, `active.account`, `role:admin` | Mark job pending/approved/rejected |
| GET | `/approved-jobs` | `auth`, `active.account`, `role:admin` | Approved job list |
| GET | `/rejected-jobs` | `auth`, `active.account`, `role:admin` | Rejected job list |
| GET | `/pending-jobs` | `auth`, `active.account`, `role:admin` | Pending job list |

Admin create user example:

```http
POST /admin/users HTTP/1.1
Content-Type: application/x-www-form-urlencoded

_token=csrf-token
first_name=Grace
last_name=Adeyemi
username=grace
email=grace@example.com
password=StrongPass!123
password_confirmation=StrongPass!123
role=employer
status=active
```

Admin job review example:

```http
PATCH /admin/jobs/42/status HTTP/1.1
Content-Type: application/x-www-form-urlencoded

_token=csrf-token
status=approved
```

## Environment Variables And Configuration

The starter values live in `.env.example`.

Core application:

| Variable | Purpose | Local default |
| --- | --- | --- |
| `APP_NAME` | Application name used in UI/mail/config | `Laravel` |
| `APP_ENV` | Environment name | `local` |
| `APP_KEY` | Encryption key for cookies, encrypted attributes, and app cryptography | blank until generated |
| `APP_PREVIOUS_KEYS` | Optional previous app keys for key rotation | blank |
| `APP_DEBUG` | Debug error pages | `true` locally, must be `false` in production |
| `APP_URL` | Canonical app URL for assets, links, mail, storage URLs | `http://localhost` |
| `APP_LOCALE`, `APP_FALLBACK_LOCALE`, `APP_FAKER_LOCALE` | Localization/faker config | `en`, `en`, `en_US` |
| `BCRYPT_ROUNDS` | Password hashing cost | `12` |

Database:

| Variable | Purpose |
| --- | --- |
| `DB_CONNECTION` | `sqlite`, `mysql`, `pgsql`, or `sqlsrv` |
| `DB_DATABASE` | SQLite path or database name |
| `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD` | Server database credentials |
| `DB_URL` | Optional database URL |
| `DB_FOREIGN_KEYS` | SQLite foreign key enforcement |

Cache/session/queue:

| Variable | Purpose |
| --- | --- |
| `SESSION_DRIVER`, `SESSION_LIFETIME`, `SESSION_ENCRYPT`, `SESSION_DOMAIN`, `SESSION_SECURE_COOKIE`, `SESSION_SAME_SITE` | Session persistence and cookie security |
| `CACHE_STORE`, `CACHE_PREFIX` | Cache backend and namespace |
| `QUEUE_CONNECTION` | Queue backend, default `database` |
| `DB_QUEUE_TABLE`, `DB_QUEUE_RETRY_AFTER` | Database queue settings |
| `REDIS_*` | Redis cache/session/queue settings when Redis is used |

Mail:

| Variable | Purpose |
| --- | --- |
| `MAIL_MAILER` | Mail transport, default `log` |
| `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_SCHEME` | SMTP settings |
| `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` | Sender identity |
| `MAIL_LOGO_URL` | Optional branded email logo |
| `MAIL_FOOTER_SUPPORT_URL`, `MAIL_FOOTER_PRIVACY_URL`, `MAIL_FOOTER_TERMS_URL`, `MAIL_FOOTER_UNSUBSCRIBE_URL` | Optional custom mail footer links |
| `POSTMARK_API_KEY`, `RESEND_API_KEY`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION` | Provider credentials where relevant |

Filesystem:

| Variable | Purpose |
| --- | --- |
| `FILESYSTEM_DISK` | Default disk, default `local` |
| `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`, `AWS_URL`, `AWS_ENDPOINT`, `AWS_USE_PATH_STYLE_ENDPOINT` | S3/object-storage settings |

Logging/monitoring:

| Variable | Purpose |
| --- | --- |
| `LOG_CHANNEL`, `LOG_STACK`, `LOG_LEVEL` | Log channel and severity |
| `LOG_DAILY_DAYS` | Retention for daily logs |
| `LOG_SLACK_WEBHOOK_URL` | Slack critical-alert destination |
| `PAPERTRAIL_URL`, `PAPERTRAIL_PORT` | Papertrail destination |

Seeder-specific optional variables:

- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`

These are referenced by `Database\Seeders\AdminSeeder`. The primary `DatabaseSeeder` currently creates `admin@example.com`, `test@example.com`, and `applicant@example.com` using factory defaults.

## Installation And Setup Instructions

Prerequisites:

- PHP 8.2 or newer.
- Composer.
- Node.js and npm.
- A database server or SQLite.
- PHP extensions commonly required by Laravel: `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `mbstring`, `openssl`, `pdo`, `session`, `tokenizer`, `xml`.
- Recommended PHP extension: `intl`. Some Laravel CLI database formatting commands fail without it.
- Optional but recommended for image-heavy workflows: `gd` or `imagick`.

Fresh setup:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

For SQLite:

```bash
touch database/database.sqlite
```

Then set:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

For MySQL/PostgreSQL, create the database and set the appropriate `DB_*` values.

Finish setup:

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Default seeded accounts from `DatabaseSeeder`:

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@example.com` | `password` |
| Employer | `test@example.com` | `password` |
| Applicant | `applicant@example.com` | `password` |

Important: replace or delete seeded users in any non-local environment.

## Running The Project Locally

Option A: Composer's combined development script:

```bash
composer dev
```

This starts:

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `npm run dev`

Option B: run processes separately:

```bash
php artisan serve
php artisan queue:work
npm run dev
```

Then open:

```text
http://127.0.0.1:8000
```

If using Laravel Herd, point Herd at the project and use the Herd-provided local domain. Keep `npm run dev` running for Vite during frontend development and keep a queue worker/listener running for queued welcome emails.

## Build, Development, And Production Commands

Project setup:

```bash
composer setup
```

Development:

```bash
composer dev
npm run dev
php artisan queue:work
php artisan pail
php artisan tinker
```

Build:

```bash
npm run build
```

Testing:

```bash
php artisan test
composer test
```

Formatting:

```bash
vendor/bin/pint
```

Database:

```bash
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed
php artisan migrate:status
```

Storage:

```bash
php artisan storage:link
```

Project-specific maintenance:

```bash
php artisan app:create-default-users
php artisan app:encrypt-application-document-numbers
php artisan app:encrypt-application-document-numbers --commit
php artisan app:move-application-documents-private
php artisan app:move-application-documents-private --commit
php artisan app:move-application-documents-private --commit --delete-public
```

Production optimization:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

Clear caches when needed:

```bash
php artisan optimize:clear
```

## Deployment Guidelines

Recommended deployment checklist:

1. Provision PHP 8.2+, Composer, Node build tooling or CI-built assets, web server, database, queue worker, and shared storage.
2. Configure the web server document root to `public/`.
3. Set production `.env` values:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://your-domain.example`
   - strong `APP_KEY`
   - production database credentials
   - production mail provider
   - durable cache/session/queue drivers
   - secure cookie settings
4. Install PHP dependencies with `composer install --no-dev --optimize-autoloader`.
5. Build assets with `npm ci && npm run build`, preferably in CI.
6. Ensure writable permissions for `storage/` and `bootstrap/cache/`.
7. Run `php artisan storage:link` for public images/logos.
8. Run `php artisan migrate --force`.
9. Warm optimized caches with `php artisan optimize`.
10. Start queue workers with Supervisor/systemd/platform worker support.
11. Configure log shipping and uptime/error monitoring.
12. Rotate or remove seeded/default users.

For zero-downtime deployments:

- Put the app into maintenance mode only if the platform cannot perform atomic release swaps.
- Run migrations before or during release using backward-compatible changes.
- Restart PHP-FPM/queue workers after code changes.
- Run `php artisan queue:restart` so workers pick up the new code.
- Use shared object storage for uploads when running multiple web nodes.

Legacy data migration:

- Run `php artisan app:encrypt-application-document-numbers` first as a dry run.
- Run `php artisan app:encrypt-application-document-numbers --commit` after reviewing output.
- Run `php artisan app:move-application-documents-private` first as a dry run.
- Run `php artisan app:move-application-documents-private --commit --delete-public` only after verifying private copies.

## Security Best Practices

Implemented security controls:

- Session authentication with CSRF protection on web forms.
- Login rate limiting by email/IP.
- Named route throttles:
  - `downloads`: 60 per minute by user ID or IP.
  - `application-submit`: 6 per minute by user ID or IP.
  - `uploads`: 12 per minute by user ID or IP.
- Role middleware for admin/employer/applicant route boundaries.
- Active-account middleware logs suspended users out of protected routes.
- Form Request validation for contact forms, profile updates, application submission, and review actions.
- Strong password rule on public registration.
- Password hashing through Laravel Hash and the Eloquent `hashed` cast.
- Current password required for account deletion and password updates.
- Admin self-protection and last-active-admin protection.
- Private storage for sensitive application documents.
- File upload validation for profile images, job logos, identity documents, and education documents.
- Document numbers are encrypted at rest and displayed masked in review views.
- Document streaming sends `Cache-Control: private, no-store` and `X-Content-Type-Options: nosniff`.
- Blade output escaping protects normal rendered variables from XSS.
- Soft deletes preserve user records for recovery/audit workflows.

Production recommendations:

- Set `APP_DEBUG=false`.
- Serve only over HTTPS and enable `SESSION_SECURE_COOKIE=true`.
- Consider `SESSION_ENCRYPT=true` for sensitive deployments.
- Keep `SESSION_SAME_SITE=lax` or stricter unless cross-site embedding is required.
- Do not change `APP_KEY` casually; encrypted document numbers depend on it. Use `APP_PREVIOUS_KEYS` and a deliberate rotation plan if changing keys.
- Delete or rotate seeded/default accounts and weak passwords immediately outside local development.
- Store uploaded application documents outside the public web root; keep using the private `local` disk or private S3/object storage.
- Add antivirus/malware scanning for uploaded documents before making them available to employers/admins.
- Do not trust browser-reported MIME types alone for high-assurance uploads; add server-side content inspection where possible.
- Add a Content Security Policy, HSTS, `X-Frame-Options`/`frame-ancestors`, and other response headers at the web server or middleware layer.
- Keep Composer and npm dependencies patched.
- Restrict admin routes at the network layer where appropriate.
- Log administrative changes and security-sensitive events.
- Back up databases and private storage together.
- Ensure `storage/` and `.env` are not web-accessible.
- Use least-privilege database credentials.
- Limit upload size at both Laravel validation and web-server/PHP configuration levels.

## Scalability Considerations

Current scale-friendly implementation details:

- Domain logic is separated into models, controllers, requests, services, middleware, notifications, and commands.
- Pagination is used for public jobs, employer jobs, applicant documents, applicant notifications, applicant applications, admin users, admin jobs, and employer candidate tables.
- Database indexes exist for common filters and dashboard aggregations.
- Queue infrastructure exists and is used by the welcome email path.
- Dashboard date-range aggregation supports SQLite, PostgreSQL, and MySQL date expressions.
- Static theme assets live under `public/` and Vite builds optimized assets.

Recommended improvements as traffic grows:

- Move cache, sessions, queues, and rate limiter backing store to Redis.
- Use MySQL or PostgreSQL for production instead of SQLite.
- Move uploads to private object storage such as S3 and serve public assets through a CDN.
- Add database indexes based on query plans for high-traffic filters:
  - `job_posts(status, category, created_at)`
  - `application_forms(job_id, status, submitted_at)`
  - `application_forms(user_id, submitted_at)`
  - `application_documents(application_form_id, status)`
  - `notifications(notifiable_type, notifiable_id, read_at, created_at)`
- Keep search fields bounded or introduce a dedicated search backend when free-text job/user search becomes expensive.
- Add cursor pagination for very large tables if offset pagination becomes slow.
- Push heavier mail, file scanning, image processing, and reporting workloads to background jobs.
- Cache dashboard aggregates for short windows if admin/employer dashboards become expensive.
- Horizontally scale stateless web nodes behind a load balancer.
- Use shared storage/object storage so all nodes can access uploaded files.
- Run queue workers as an independently scalable process group.
- Add database read replicas for reporting if dashboard traffic grows.
- Configure CDN caching for immutable built assets and static theme files.
- Monitor slow queries and add indexes from production query evidence.

## Logging, Monitoring, And Error Handling

Current behavior:

- Laravel logging uses `LOG_CHANNEL=stack` and `LOG_STACK=single` by default.
- Local logs write to `storage/logs/laravel.log`.
- `php artisan pail` is available for tailing logs during development.
- `/up` is registered as the framework health endpoint.
- Custom 404 rendering is configured in `bootstrap/app.php` and sends users to a branded 404 page with a safe previous/home link.
- Application and document review histories provide domain-level audit trails.
- Failed queued jobs are stored in `failed_jobs` when using the database queue failed-job driver.
- Database notifications are stored in `notifications`.

Recommended production monitoring:

- Send logs to stdout/stderr, a log aggregator, or a managed provider.
- Configure error tracking such as Laravel Nightwatch, Sentry, Bugsnag, or a platform-native equivalent.
- Monitor queue depth, failed jobs, and worker health.
- Alert on 5xx rate, failed login spikes, upload failures, failed queue jobs, and database connectivity failures.
- Track slow queries and request latency by route.
- Add audit logging for admin user changes, job approvals, application reviews, document downloads, and document status changes.
- Back up `storage/app/private` or object-storage buckets together with database snapshots.

## Testing Strategy And Recommended Test Cases

Current testing:

- Pest is configured in `phpunit.xml`.
- Test environment uses in-memory SQLite, array mail, sync queues, array sessions, and array cache.
- Existing Feature tests cover:
  - Auth registration, login, logout, password reset, password confirmation, password update.
  - RBAC and suspended-account access.
  - Admin dashboard metrics and date filters.
  - Admin user management.
  - Admin job moderation.
  - Employer dashboard scoping.
  - Employer job CRUD and upload validation.
  - Applicant dashboard/profile completion.
  - Applicant read-only profile access.
  - Application wizard, submission, duplicate prevention, review, notifications.
  - Document number encryption and masking.
  - Document preview/download authorization and MIME handling.
  - Maintenance commands for document encryption and private storage migration.
  - Public pages, contact form, custom 404, and SweetAlert integration.

Run tests:

```bash
php artisan test
```

Recommended additional tests:

- Rate limiter behavior for `downloads`, `application-submit`, and `uploads`.
- CSRF rejection tests for sensitive POST/PATCH/PUT/DELETE routes.
- File upload security tests for renamed extensions, oversized files, and mismatched MIME/content.
- Authorization tests for admin access to all document download/preview flows.
- Queue tests for welcome email dispatch and failed mail handling.
- Browser/regression tests for multi-step application forms and dependent state/LGA controls.
- Tests for public visibility of approved jobs versus pending/rejected jobs.
- Tests for dashboard aggregate correctness on MySQL/PostgreSQL if production does not use SQLite.
- Tests for `APP_KEY` rotation behavior if encrypted legacy data exists.

## Troubleshooting Guide And Common Issues

`php artisan db:table` fails with an `intl` error:

- Install/enable the PHP `intl` extension for the CLI PHP binary.
- On Windows, enable `extension=intl` in the active `php.ini`.
- Confirm with `php -m`.

Uploaded profile images or job logos do not display:

- Run `php artisan storage:link`.
- Confirm files exist under `storage/app/public`.
- Confirm `APP_URL` is correct.

Application document preview/download returns 404:

- Confirm the file exists on the private `local` disk under `storage/app/private`.
- If documents were previously public, run `php artisan app:move-application-documents-private` as a dry run, then commit after review.

Application document preview returns 415:

- Only PDF, JPEG, and PNG are previewable inline.
- Use the download route for other allowed file types.

Duplicate application validation fires:

- The schema and service prevent one applicant from applying to the same job twice.
- Use the existing application detail page instead of resubmitting.

403 on dashboards or management pages:

- Verify the authenticated user's `role`.
- Verify the user's `status` is `active`.
- Verify employer ownership of the job/application being accessed.

Welcome email does not send:

- The registration flow queues `WelcomeEmail`.
- Start a queue worker with `php artisan queue:work` or set `QUEUE_CONNECTION=sync` locally.
- Configure a real `MAIL_MAILER` in production.

Vite manifest or assets are missing:

- During development, run `npm run dev`.
- In production, run `npm run build`.

Migrations fail on a fresh database:

- Run `composer install` first so Doctrine/schema dependencies available to Laravel are present.
- Ensure the configured database exists.
- For SQLite, create `database/database.sqlite` and set `DB_DATABASE` to the correct path.
- Use `php artisan migrate:fresh --seed` only in local/dev because it drops all tables.

Encrypted document numbers become unreadable:

- Do not change `APP_KEY` without a key rotation plan.
- If rotation is necessary, use Laravel previous keys and test decrypting existing `application_documents.document_number` values.

Admin seeded login does not work:

- `DatabaseSeeder` creates `admin@example.com` with password `password` through the factory.
- `app:create-default-users` creates `saviourpaul24@gmail.com` with password `123456789`.
- Do not use these defaults outside local development.

## Future Enhancements And Roadmap

Near-term:

- Normalize the database default for `users.status` to match the `UserStatus` enum and model default.
- Replace hardcoded default-user credentials with environment-only secure provisioning.
- Convert repeated authorization checks into Laravel policies.
- Add explicit audit events for admin user changes, job reviews, and document downloads.
- Add rate limiter tests.
- Add upload content inspection and malware scanning.
- Add a real contact-form persistence or notification workflow.

Product:

- Employer onboarding and admin approval workflow.
- Applicant resume/CV parsing and profile import.
- Saved jobs/favorites if that feature is intended for production.
- Interview scheduling, assessment templates, and email-template management backed by database tables.
- Rich notification preferences and read/unread notification management.
- Search relevance improvements for jobs and candidates.
- Reporting exports for admins and employers.

Platform:

- Redis-backed cache, session, queue, and rate limiting.
- Private S3/object-storage disk for application documents.
- CDN for public assets and optimized image delivery.
- Centralized monitoring and alerting.
- CI pipeline for `composer install`, `npm ci`, `vendor/bin/pint`, `php artisan test`, and `npm run build`.
- Blue/green or atomic-release deployment strategy.
- Database backup and restore drills.
