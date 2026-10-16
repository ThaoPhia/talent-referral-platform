# Talent Referral Platform

A referral-driven recruiting platform where members recommend candidates for open roles and recruiters manage the resulting referrals.

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

Run the main checks before opening a pull request:

```bash
composer test
composer analyse
npm run lint
npm run typecheck
```

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

The SQLite database volume is preserved when the container is replaced. The local `public/` directory is mounted into the container so Laravel can see Vite's live hot file and development fonts manifest.

### Local HTTPS on macOS

Install mkcert and trust its local certificate authority (macOS may request your password):

```bash
brew install mkcert
mkcert -install
```

Map `talentreferralplateform.test` to `127.0.0.1` in `/etc/hosts`, then configure:

```dotenv
APP_PORT=8001
APP_URL=https://talentreferralplateform.test:8001
```

Run `npm run docker:rebuild`. For HTTPS URLs, the rebuild script generates a local certificate and publishes Apache's HTTPS port on the port in `APP_URL` (443 if omitted). Certificates and private keys stay in the ignored `docker/certs/` directory, are excluded from image builds, and are mounted read-only. The rebuild honors `APP_URL` instead of replacing it with localhost.

If Vite is running, restart `npm run dev` after generating the certificates. Vite uses the same certificates and the hostname from `APP_URL`, preventing mixed-content and WebSocket errors on HTTPS pages. Missing certificates cause Vite to fail explicitly; run the rebuild first. HTTP URLs keep the existing non-TLS workflow.

## Project requirements
    Tables: 
        users(type: admin, normal/referrer)
            referrer (name, email, resume URL, note)
        jobs(title, description, location, post, date, status(active, archived))
        referrals (date, user_id, referrer_id, job_id, status(pending, accepted, rejected))
    Usages:
        Backend: Admin create/edit/view jobs; Admin view/edit referrals 
        Front-end: 
            Guest/member search/view jobs; 
            Only member can click on "Refer a candidate" button when viewing a job listing

