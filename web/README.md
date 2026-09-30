# BBH Farm Web

Laravel web application for the BBH Farm admin dashboard and public certificate verification site.

This application is the user interface layer. It does not own the core farm data; it calls the backend in `../api` through `BBH_API_BASE_URL`. Keeping this boundary makes the API reusable for future mobile apps, integrations, or separate frontend clients.

## Main Features

- Public company and farm information pages
- Public certificate verification by certificate number, QR/token, or PDF upload
- Admin login and session management backed by the API
- Admin dashboard for livestock, pens, breeding, pregnancy checks, births, postnatal care, health records, vaccinations, certificates, reports, RSA keys, and audit logs
- Certificate preview, PDF download, and XLSX report download through API endpoints

## Tech Stack

- PHP 8.3+
- Laravel 13
- Blade templates
- Vite 8
- Tailwind CSS 4
- Laravel HTTP client for API communication
- PHPUnit
- Laravel Pint

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm
- SQLite for a simple local setup, or a separate MySQL/MariaDB database
- Running BBH Farm API application

## Installation

From the monorepo root:

```powershell
cd web
composer install
npm install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

For a local SQLite setup, set these values in `web/.env` and remove the copied `DB_DATABASE`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` lines so Laravel uses `database/database.sqlite`:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
DB_CONNECTION=sqlite
SESSION_DRIVER=database
CACHE_STORE=database
BBH_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Create the SQLite file only if it does not exist, then run web migrations:

```powershell
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite | Out-Null }
php artisan migrate
```

For MySQL/MariaDB instead, use `DB_CONNECTION=mysql` and configure `DB_DATABASE=bbh_farm_web`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, and `DB_PASSWORD` in `web/.env`. Create that database before migrating. The web database stores sessions and cache; livestock records live in the API database.

Build frontend assets:

```powershell
npm run build
```

Start the web server:

```powershell
php artisan serve --port=8001
```

Open:

- Public site: `http://127.0.0.1:8001`
- Admin login: `http://127.0.0.1:8001/login`
- Password reset: `http://127.0.0.1:8001/lupa-kata-sandi`

The API seeder creates one super admin and two admins. The two admins start with random passwords and must set their own through the email reset page. SMTP is configured in the API, not the web app; there is no SMS OTP step. See [../api/README.md](../api/README.md) for seeding details.

## Development

Run the Vite dev server when actively editing CSS or JavaScript:

```powershell
npm run dev
```

Run tests:

```powershell
php artisan test
```

Check formatting:

```powershell
vendor/bin/pint --test
```

Apply formatting:

```powershell
vendor/bin/pint
```

## API Dependency

Most admin pages require a valid API token and a reachable API service. For local development, run the API in another terminal:

```powershell
cd ../api
php artisan serve --port=8000
```

The web app reads the API base URL from:

```env
BBH_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

If the API is unavailable, public verification and auth flows show user-facing error messages instead of exposing a raw exception page.

## Security Notes

- Do not commit `.env`, sessions, logs, uploaded files, or other runtime data.
- Use `APP_DEBUG=false` in production.
- Protect the web app with HTTPS in production because admin sessions and certificate workflows depend on secure transport.
