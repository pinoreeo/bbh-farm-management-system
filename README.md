# BBH Farm

BBH Farm is a livestock management system for Bumiku Bumimu Hijau Farm. It supports dairy goat operations, structured livestock records, admin workflows, public farm pages, and electronically verifiable livestock certificates.

## Architecture

This repository intentionally keeps the backend API and web interface as separate Laravel applications:

- `api` provides authentication, livestock data, breeding workflows, certificate issuance, RSA-SHA256 signing, public verification, reports, and audit logs.
- `web` provides the Blade-based admin dashboard, public company pages, certificate preview/download screens, and public certificate verification interface.

The API boundary is intentional so the same backend can later serve other clients such as a mobile app, partner integration, kiosk, or a separate frontend without rewriting the core farm logic.

## Tech Stack

- API: PHP 8.2+, Laravel 11, Laravel Sanctum, MySQL/MariaDB, OpenSSL RSA-SHA256, L5 Swagger, PHPUnit, PHPStan/Larastan, Laravel Pint
- Web: PHP 8.3+, Laravel 13, Blade, Vite 8, Tailwind CSS 4, PHPUnit, Laravel Pint
- Certificates: canonical payload hash, RSA-SHA256 digital signature, QR/token verification, and optional official PDF integrity check

## Requirements

- PHP with common Laravel extensions: `openssl`, `mbstring`, `xml`, `pdo`, and a database driver such as `pdo_mysql`
- Composer
- Node.js and npm for the web asset pipeline
- MySQL or MariaDB for normal local/deployed usage

## Local Setup

Install and configure the API:

```bash
cd api
composer install
cp .env.example .env
php artisan key:generate
```

Update `api/.env` at minimum:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_DATABASE=bbh_farm
DB_USERNAME=root
DB_PASSWORD=
BBH_ADMIN_EMAIL=admin@farm.local
BBH_ADMIN_PASSWORD=change-this-password
BBH_PUBLIC_WEB_URL=http://127.0.0.1:8001
L5_SWAGGER_ENABLED=true
```

Run the API database setup:

```bash
php artisan migrate --seed
php artisan serve --port=8000
```

Install and configure the web app in a second terminal:

```bash
cd web
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Update `web/.env` at minimum:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
DB_DATABASE=bbh_farm_web
DB_USERNAME=root
DB_PASSWORD=
BBH_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Run the web app:

```bash
php artisan migrate
npm run build
php artisan serve --port=8001
```

Open:

- Web/public site: `http://127.0.0.1:8001`
- Admin login: `http://127.0.0.1:8001/login`
- API base URL: `http://127.0.0.1:8000/api/v1`
- API documentation when enabled: `http://127.0.0.1:8000/api/documentation`

## Development

Useful commands:

```bash
cd api
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
```

```bash
cd web
php artisan test
npm run build
vendor/bin/pint
```

## Environment Notes

- Keep `.env`, local databases, logs, generated private keys, and uploaded private files out of version control.
- `api/.env.example` defaults to production-style values; change them for local development.
- `web` communicates with `api` through `BBH_API_BASE_URL`, so both applications must be running for admin and verification flows that call the backend.
- In production, keep `APP_DEBUG=false` and `L5_SWAGGER_ENABLED=false` unless API documentation is intentionally exposed behind proper access control.

## Electronic Certificates

BBH Farm issues electronic livestock certificates with a canonical data payload, SHA-256 hash, and RSA-SHA256 digital signature. Each certificate includes a QR/token reference that can be checked through the public verification page.

The verification flow validates certificate status, signed payload integrity, RSA signature authenticity, and official PDF integrity when a PDF document is uploaded.
