# Talent Referral Platform

A referral-driven recruiting platform where referrers recommend candidates for open roles and recruiters manage the resulting referrals.

This project is more of a starter kit than a complete, production-ready application. It provides the foundation and core flows to build on, not a fully working product.

## Tech Stack

- **Backend:** Laravel 13, PHP 8.3+, Laravel Fortify
- **Frontend:** Vue 3, Inertia.js v3, Vite 8, TypeScript
- **UI:** PrimeVue 4, Tailwind CSS 4, Lucide Vue icons
- **Data and types:** Spatie Laravel Data, Spatie TypeScript Transformer
- **Testing and quality:** PHPUnit, PHPStan/Larastan, Laravel Pint, ESLint, vue-tsc
- **Default local database:** SQLite

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js 22 or newer
- npm

## Setup

Install PHP and JavaScript dependencies:

```bash
composer install
npm install
```

Create the local environment file and application key:

```bash
cp .env.example .env
php artisan key:generate
```

Create the default SQLite database file and run migrations:

```bash
touch database/database.sqlite
php artisan migrate
```

On Windows PowerShell, use this command instead of `touch`:

```powershell
New-Item -ItemType File -Path database/database.sqlite -Force
php artisan migrate
```

## Local Development

### Referral invitations

Admins and the owning referrer can use **Resend invitation** in their referral list
to retry an invitation after a mail outage. Only pending referrals for open jobs can
be resent; creation and resend requests each have independent limits of five per
minute per user and per IP. Creation limits apply before database writes or email
delivery. A failed send keeps the
referral and shows a warning so it can be retried later.

Candidate responses use an atomic pending-to-decided update. Only the winning
request stores an uploaded resume and sends a response notification. File storage
happens after the status update inside the same database transaction; a storage
failure rolls back the response so the candidate can retry.

When upgrading an older database, the invitation-tracking migration keeps the newest
referral (highest ID) for each job/candidate pair before adding the unique index.
Older duplicates are preserved in `referral_duplicate_archive`, including their
original IDs, complete original row data, and the retained referral ID. They are
excluded from active referral lists and statistics. Rolling back that migration
restores the archived rows. Already-migrated databases do not rerun this migration.

### Public pages and contact form

The public About Us (`/about`) and Contact Us (`/contact`) pages are linked in the shared footer.
Contact messages are emailed to `MAIL_FROM_ADDRESS`, with the visitor's email as Reply-To.
Configure your mail transport before deploying; local messages can be viewed in Mailpit.
Submissions are limited to five per minute per IP address.
Contact submissions, referral creation, invitation viewing/responses (five per minute
per IP), and resends use separate rate-limit counters.
These application limiters are unlimited in the `testing` environment by default.
Tests specifically covering throttling opt in with `config(['app.test_rate_limits' => true])`.

Set `CONTACT_ADDRESS` in `.env` to change the displayed postal address. The default in
`config/contact.php` is a placeholder, not a real office address; replace it before launch.
After changing environment configuration, refresh the config cache (or rebuild the Docker container).

Start the full development stack:

```bash
npm run dev:composer
```

This runs the Laravel server, queue listener, Vite dev server, and TypeScript transformer watcher through the Composer `dev` script.

Use the frontend-only Vite server when the Laravel app is already running separately:

```bash
npm run dev
```

Stop active local dev processes started by the full stack:

```bash
npm run dev:kill
```

## Quality Checks

Run the PHP tests:

```bash
npm test
```

Pass PHPUnit options after `--`, for example `npm test -- --filter=ReferralWorkflowTest` or `npm test -- tests/Feature/JobsAndReferralsTest.php`.

Run every check that CI runs (Pint, PHPStan, tests, ESLint, and type checking) before opening a pull request:

```bash
npm run check
```

It stops at the first failing check. `npm run lint` and `vendor/bin/pint` fix issues automatically; `npm run check` only reports them.

Build production assets:

```bash
npm run build
```

## Docker

The `Dockerfile` builds a self-contained production-style image (Vite assets + Composer deps baked in via multi-stage build). To build and run it:

```bash
npm run docker:rebuild
```

On macOS, install and start Docker Desktop before running this command. The shell script (`docker/rebuild.sh`) works on macOS and Linux without PowerShell. It builds the image, stops/removes any existing container, and starts a fresh one using the local `.env` file (`--env-file .env`). If a Docker command fails, the script stops and reports the error.

The image name, container name, volume name, and port are all read from `.env`, with fallback defaults if unset:

```dotenv
APP_PORT=8000
DOCKER_CONTAINER_NAME=talent-referral-platform
DOCKER_IMAGE_NAME=talent-referral-platform:latest
DOCKER_VOLUME_NAME=talent-referral-platform-sqlite
```

The SQLite database volume is preserved when the container is replaced. Candidate resume uploads are stored in a `resumes/` folder on the same volume (or `DOCKER_DATA_DIR` folder), so they survive rebuilds too. To keep the database in a folder on your computer instead (for example, to open it in DBeaver), set `DOCKER_DATA_DIR` to that folder's path in `.env`. The rebuild scripts create the folder if needed and mount it in place of the volume. The local `public/` directory is mounted into the container so Laravel can see Vite's live hot file and development fonts manifest.

The container also runs Laravel's scheduler (`php artisan schedule:work`) next to Apache, so scheduled tasks such as the nightly `jobs:archive-expired` run automatically and appear in `docker logs`. The scheduler restarts itself if it stops. Set `RUN_SCHEDULER=false` in `.env` to turn it off.

Scheduled tasks follow the server's timezone, not `APP_TIMEZONE`, so the nightly archive runs at 11:59 PM server time. `docker:rebuild` passes your computer's timezone to the container as `TZ`. To use a different one, set `TZ` (the container's clock) or `SCHEDULE_TIMEZONE` (the scheduler only) in `.env`. Check the schedule with:

```bash
docker exec talent-referral-platform php artisan schedule:list
```

Because `public/` is mounted from your computer, the container serves the assets in your local `public/build` instead of the ones built into the image. You don't need to run `npm run dev` after a rebuild. Run it for hot reloading while you edit the frontend. Otherwise, run `npm run build` after frontend changes so `public/build` is up to date.

Local rebuilds (`APP_ENV=local`) include Composer development dependencies, including Faker for sample data. Other environments and direct Docker builds omit development dependencies by default.

To populate the Docker database with sample jobs, users, and referrals, rebuild after seeder changes, then run:

```bash
npm run docker:rebuild
docker exec talent-referral-platform php artisan db:seed
```

Replace the container name if you customized `DOCKER_CONTAINER_NAME`. Run Artisan inside Docker when `DB_DATABASE` points to `/var/www/data/database.sqlite`; that path belongs to the container, not the host. For a host-based setup with its own database, use `php artisan db:seed` instead.

The sample-data seeder runs only when `APP_ENV=local` and can be rerun safely. It creates 25 sample jobs, 25 sample users, 15 referrals, and a local admin account (`sample.admin@example.test`, password `password`). Sample user accounts also use the password `password`.

### Viewing emails locally

`docker:rebuild` can start a [Mailpit](https://mailpit.axllent.org) inbox that catches every email the app sends (referral invitations, referrer alerts, password resets) instead of delivering it. Point mail at it in `.env`:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

Then run `npm run docker:rebuild` and open http://127.0.0.1:8026. Set `DOCKER_MAILPIT_PORT` to use a different port. Mailpit runs as `<DOCKER_CONTAINER_NAME>-mailpit` on a private network shared only with the app, so it doesn't clash with other projects' Mailpit containers. Messages are kept in a Docker volume of the same name, so they survive rebuilds. To share one Mailpit (and inbox) with other projects instead, set `DOCKER_SHARED_MAILPIT` to that Mailpit's container name, for example `DOCKER_SHARED_MAILPIT=phia-property-rental-mailpit-1`. The rebuild starts that container if it's stopped, joins its Docker network, and sends mail to it instead of starting this project's own Mailpit. You don't need to start the other project. If the container doesn't exist (for example after `docker compose down` in the other project), the rebuild warns and falls back to this project's own Mailpit on port 8026. Messages from each app show their `MAIL_FROM_NAME` as the sender. Docker's `--env-file` doesn't expand variables, so set it to a literal name such as `MAIL_FROM_NAME="Talent Referral Platform"` rather than `"${APP_NAME}"`. Without `MAIL_HOST=mailpit`, no Mailpit container is started; `MAIL_MAILER=log` writes emails to `storage/logs/laravel.log` instead.

### Local HTTPS on macOS

Install mkcert and trust its local certificate authority (macOS may request your password):

```bash
brew install mkcert
mkcert -install
```

Map `talentreferralplateform.test` to `127.0.0.1` in `/etc/hosts`, then configure:

```dotenv
APP_PORT=8001
APP_URL=https://talentreferralplateform.test
```

Run `npm run docker:rebuild`. `APP_PORT` controls the host port published by Docker and is added to the runtime `APP_URL` when it is nonstandard; for HTTPS, the script publishes that host port to Apache's HTTPS port. Certificates and private keys stay in the ignored `docker/certs/` directory, are excluded from image builds, and are mounted read-only. Set `APP_URL` to the protocol and hostname only; the rebuild preserves those while adding the configured port as needed.

When sharing port-free HTTPS domains with PhiaPropertyRental, set
`DOCKER_PROXY_NETWORK=local-https` and
`APP_URL=https://talentreferralplateform.test`. Create the `local-https` network
first (PhiaPropertyRental's `docker:restart` also creates it). In this mode the
rebuild script joins that network without publishing a host port, and leaves
`APP_URL` port-free; `APP_PORT` is ignored. The shared TLS passthrough proxy in
PhiaPropertyRental's `docker/proxy/compose.yaml` owns host port 443 and routes
each hostname to its own container. HTTPS certificates remain managed here
with mkcert. Stopping the PhiaPropertyRental app does not stop the shared proxy.

If Vite is running, restart `npm run dev` after generating the certificates. Vite uses the same certificates and the hostname from `APP_URL`, preventing mixed-content and WebSocket errors on HTTPS pages. Missing certificates cause Vite to fail explicitly; run the rebuild first. HTTP URLs keep the existing non-TLS workflow.

## Project requirements
    Tables: 
        users(type: admin, referrer, candidate)
            candidate (name, email)
        jobs(title, description, location, post, date, status(active, archived))
        referrals (date, user_id, candidate_id, job_id, status(pending, accepted, rejected), referrer_note, candidate_note, candidate_resume (link or uploaded file), viewed_at, responded_at)
    Usages:
        Backend: Admin create/edit/view jobs; Admin view/edit referrals 
        Front-end: 
            Guest/referrer search/view jobs;
            Only referrers can click on "Refer a candidate" button when viewing a job listing
