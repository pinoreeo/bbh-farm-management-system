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
- MySQL or MariaDB for normal local/deployed usage
- Running BBH Farm API application

## Installation

From the monorepo root:

```bash
cd web
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure at least these values in `web/.env`:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
DB_DATABASE=bbh_farm_web
DB_USERNAME=root
DB_PASSWORD=
BBH_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Run local database migrations:

```bash
php artisan migrate
```

Build frontend assets:

```bash
npm run build
```

Start the web server:

```bash
php artisan serve --port=8001
```

Open:

- Public site: `http://127.0.0.1:8001`
- Admin login: `http://127.0.0.1:8001/login`

## Development

Run the Vite dev server when actively editing CSS or JavaScript:

```bash
npm run dev
```

Run tests:

```bash
php artisan test
```

Check formatting:

```bash
vendor/bin/pint --test
```

Apply formatting:

```bash
vendor/bin/pint
```

## API Dependency

Most admin pages require a valid API token and a reachable API service. For local development, run the API in another terminal:

```bash
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
