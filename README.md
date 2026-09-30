# BBH Farm

BBH Farm is a livestock management system for Bumiku Bumimu Hijau Farm. It supports dairy goat operations, structured livestock records, admin workflows, public farm pages, and electronically verifiable livestock certificates.

## Architecture

This repository intentionally keeps the backend API and web interface as separate Laravel applications:

- `api` provides authentication, livestock data, breeding workflows, certificate issuance, RSA-SHA256 signing, public verification, reports, and audit logs.
- `web` provides the Blade-based admin dashboard, public company pages, certificate preview/download screens, and public certificate verification interface.

The API boundary is intentional so the same backend can later serve other clients such as a mobile app, partner integration, kiosk, or a separate frontend without rewriting the core farm logic.

## Tech Stack

- API: PHP 8.2+, Laravel 12, Laravel Sanctum, MySQL/MariaDB, OpenSSL RSA-SHA256, L5 Swagger, PHPUnit, PHPStan/Larastan, Laravel Pint
- Web: PHP 8.3+, Laravel 13, Blade, Vite 8, Tailwind CSS 4, PHPUnit, Laravel Pint
- Certificates: canonical payload hash, RSA-SHA256 digital signature, QR/token verification, and optional official PDF integrity check

## Requirements

- PHP with common Laravel extensions: `openssl`, `mbstring`, `xml`, `pdo`, and a database driver such as `pdo_mysql`
- Composer
- Node.js and npm for the web asset pipeline
- MySQL or MariaDB for the API; the web app can use a separate MySQL database or SQLite

## Local Setup

Install and configure the API:

```powershell
cd api
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

Update `api/.env` at minimum:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_DATABASE=bbh_farm
DB_USERNAME=root
DB_PASSWORD=
BBH_ADMIN_EMAIL=superadmin@bbhfarm.com
BBH_ADMIN_PASSWORD=replace-with-a-unique-password
BBH_PUBLIC_WEB_URL=http://127.0.0.1:8001
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=noreply@your-domain.example
```

Replace the password and mail placeholders with your own local values before seeding or sending email.

Create the `bbh_farm` database first, then run the API migrations and seeders:

```powershell
php artisan migrate --seed
php artisan serve --port=8000
```

The default seeder creates `superadmin@bbhfarm.com`, `admin1@bbhfarm.com`, and `admin2@bbhfarm.com`, plus breed and certificate-type reference data. The super admin uses `BBH_ADMIN_PASSWORD`; the two admins receive random, unknown passwords and set their own through the email reset flow. SMTP is required for reset links. If `QUEUE_CONNECTION=database`, run `php artisan queue:work` in another API terminal for invitation emails.

Install and configure the web app in a second terminal:

```powershell
cd web
composer install
npm install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
php artisan key:generate
```

Update `web/.env` at minimum:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
DB_CONNECTION=mysql
DB_DATABASE=bbh_farm_web
DB_USERNAME=root
DB_PASSWORD=
BBH_API_BASE_URL=http://127.0.0.1:8000/api/v1
```

Create `bbh_farm_web` separately, then run the web app. For SQLite instead, follow [web/README.md](web/README.md).

```powershell
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

Useful commands (run each block from the repository root):

```powershell
cd api
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress
```

```powershell
cd web
php artisan test
npm run build
vendor/bin/pint --test
```

## Environment Notes

- Keep `.env`, local databases, logs, generated private keys, and uploaded private files out of version control.
- `api/.env.example` defaults to production-style values; change them for local development.
- `web` communicates with `api` through `BBH_API_BASE_URL`, so both applications must be running for admin and verification flows that call the backend.
- Admin invitations and password reset use single-use email links. Give each admin an accessible individual email address and configure the API mail settings; SMS verification is not required.
- To start with only the three accounts and no reference data, use the account-only seeder described in [api/README.md](api/README.md). Breed and certificate-type lists will then be empty until populated.
- In production, keep `APP_DEBUG=false` and `L5_SWAGGER_ENABLED=false` unless API documentation is intentionally exposed behind proper access control.

## Electronic Certificates

BBH Farm issues electronic livestock certificates with a canonical data payload, SHA-256 hash, and RSA-SHA256 digital signature. Each certificate includes a QR/token reference that can be checked through the public verification page.

The verification flow validates certificate status, signed payload integrity, RSA signature authenticity, and official PDF integrity when a PDF document is uploaded.
