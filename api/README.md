# BBH Farm API

Laravel backend API for livestock management, digital certificate issuance, and RSA-SHA256 certificate verification at BBH Farm.

This application lives inside the `api` directory of the BBH Farm monorepo. It is intentionally separated from the `web` application so the same backend can later serve a mobile app, third-party integration, kiosk, or another frontend.

## Main Features

### Livestock Management

- Animal identity and breed management
- Colony pen management
- Breeding period and breeding female records
- Pregnancy check records
- Birth event and offspring birth records
- Postnatal care records
- Weight records
- Health treatment records
- Vaccination records

### Digital Certificate Management

- Issue digital certificates for livestock records
- Supported certificate types:
  - Superior Livestock Certificate
  - Livestock Birth Certificate
  - Livestock Death Certificate
- RSA-SHA256 digital signing
- RSA key generation and activation
- Certificate revocation and reactivation
- Certificate preview and PDF export
- QR/token-based certificate verification

### Public Verification

- Verify certificate by certificate number
- Verify certificate by QR/token
- Verify official certificate PDF integrity
- Public verification logs
- Rate limiting for public verification endpoints

### Security and Audit

- Laravel Sanctum token authentication
- Admin-only protected API routes
- Admin activity logging
- Structured validation responses
- Soft-deactivation patterns for operational records

## Tech Stack

- PHP 8.2+
- Laravel 12
- Laravel Sanctum
- MySQL or another Laravel-supported relational database
- OpenSSL
- Simple QrCode
- L5 Swagger / OpenAPI
- PHPUnit
- PHPStan / Larastan
- Laravel Pint

## Installation Requirements

Make sure the following tools are installed:

- PHP 8.2 or newer
- Composer
- MySQL, MariaDB, or another supported database
- Required PHP extensions:
  - `openssl`
  - `mbstring`
  - `xml`
  - `pdo`
  - database driver extension, for example `pdo_mysql`

## Installation

From the monorepo root, enter the API application:

```powershell
cd api
```

Install dependencies:

```powershell
composer install
```

Create the environment file:

```powershell
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
```

Generate the application key:

```powershell
php artisan key:generate
```

The example environment is configured for production and has no admin password. For local use, set `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`, MySQL details for an existing `bbh_farm` database, `BBH_ADMIN_PASSWORD`, and `BBH_PUBLIC_WEB_URL=http://127.0.0.1:8001`. Configure SMTP credentials and `MAIL_FROM_ADDRESS` for email invitations and password reset; never commit `.env`.

Run migrations and the default seeders:

```powershell
php artisan migrate --seed
```

This creates one super admin (`superadmin@bbhfarm.com` by default), two admins (`admin1@bbhfarm.com` and `admin2@bbhfarm.com`), and breed/certificate-type reference data. The super admin uses `BBH_ADMIN_PASSWORD`. The admins have unique random passwords that are not disclosed; they set their passwords through `/lupa-kata-sandi` in the web app. Use individual, accessible email inboxes for these accounts. Reset links are single-use and valid for 60 minutes; SMS OTP is not used.

To reproduce an **account-only empty database**, first confirm that `DB_DATABASE` points to the intended MySQL database. Run the backup command on its own, check that it succeeds, and inspect the generated SQL file before continuing:

```powershell
php artisan bbh:backup-db
```

Only after verifying that backup, run the destructive reset below. It permanently drops all tables and records in the configured database, then recreates the schema with only the three accounts:

```powershell
php artisan migrate:fresh --seed --seeder=AccountSeeder
```

After an account-only reset, breed and certificate-type lists are empty. Populate them before creating animals or certificates:

```powershell
php artisan db:seed --class=BreedSeeder
php artisan db:seed --class=CertificateTypeSeeder
```

Start the local development server. Use port `8000` so the web application can point to `http://127.0.0.1:8000/api/v1`.

```powershell
php artisan serve --port=8000
```

With the default `QUEUE_CONNECTION=database`, admin invitations are queued after the user record is committed. Run a queue worker alongside the API server:

```powershell
php artisan queue:work --tries=3
```

The queue worker is required for invitation emails when `QUEUE_CONNECTION=database`. Password reset emails are sent directly by the API and require working SMTP settings. Do not send seed-account credentials by chat or reuse a shared default password.

The API will be available at:

```text
http://127.0.0.1:8000/api/v1
```

## API Documentation

Generate Swagger/OpenAPI documentation:

```powershell
php artisan l5-swagger:generate
```

Set `L5_SWAGGER_ENABLED=true` in the local API environment before opening the documentation page; leave it disabled unless intentionally exposed in other environments.

Open the documentation page:

```text
http://127.0.0.1:8000/api/documentation
```

## Project Structure

```text
app/
  Http/
    Controllers/Api/V1/     Thin HTTP layer: request validation and JSON responses
    Middleware/             Authentication, authorization, and activity middleware
  Models/
  Services/                 Business workflows, crypto, PDF, export, and verification logic
  Support/                  Small domain helpers

config/
database/
  migrations/
  seeders/

public/
  images/

resources/
  views/certificates/

routes/
  api.php

tests/
```

Important directories:

- `app/Http/Controllers/Api/V1` contains API controllers. Keep controllers thin: validate request, call services, return response.
- `app/Models` contains Eloquent models.
- `app/Services` contains business logic for authentication, signing, verification, PDF integrity, report export, RSA key management, certificate issuance, animal records, breeding periods, pregnancy checks, births, offspring births, and breeding female workflows.
- `app/Support` contains focused helpers such as eartag generation and pure-breed sire markers.
- `database/migrations` contains database schema definitions.
- `database/seeders` contains initial data seeders.
- `resources/views/certificates` contains certificate PDF templates.
- `public/images` contains certificate assets such as logo and signature images.
- `tests` contains automated tests.

Controller responsibilities:

- `AuthController` delegates login throttling, token lifecycle, password reset, password changes, and auth audit logs to `AuthService`.
- `RsaKeyController` delegates RSA key generation, activation, deactivation, and fingerprint handling to `RsaKeyService`.
- `CertificateController` delegates certificate issuance to `CertificateIssuanceService`, certificate numbering to `CertificateNumberService`, canonical snapshots to `CertificatePayloadSnapshotService`, and print rendering to `CertificatePrintService`.
- `AnimalController` delegates eartag generation, origin fields, photo storage, and sire marker synchronization to `AnimalService`.
- `BreedingPeriodController` delegates breeding colony validation, active-period guards, and closing flow to `BreedingPeriodService`.
- `BreedingFemaleController` delegates period entry, mating date recording, exit flow, colony destination validation, and inbreeding checks to `BreedingFemaleService`.
- `PregnancyCheckController` delegates active female context validation, pregnancy status sync, and pregnancy date rules to `PregnancyCheckService`.
- `BirthEventController` delegates dam/sire validation, pregnant dam checks, birth-date rules, and offspring marker sync to `BirthEventService`.
- `OffspringBirthController` delegates offspring animal creation, jantan pemacek markers, and birth-weight sync to `OffspringBirthService`.
- `ReportExportController` delegates XLSX row/header preparation to `ReportExportDataService`.

## Example Usage

### Login

```powershell
$body = @{ email = 'superadmin@bbhfarm.com'; password = '<your BBH_ADMIN_PASSWORD>'; device_name = 'local-client' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/auth/login' -Method Post -ContentType 'application/json' -Body $body
```

The response contains an access token. Keep it private and use it for protected admin endpoints:

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/auth/me' -Headers @{ Authorization = 'Bearer YOUR_ACCESS_TOKEN' }
```

### Create Animal

```powershell
$animal = @{ breed_id = 1; sex = 'female'; generation = 'F1'; birth_date = '2024-01-01'; life_status = 'alive' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/animals' -Method Post -Headers @{ Authorization = 'Bearer YOUR_ACCESS_TOKEN' } -ContentType 'application/json' -Body $animal
```

`breed_id` must refer to an existing breed. It will not exist immediately after an account-only reset.

### Verify Certificate by Number

```powershell
$body = @{ certificate_number = 'YOUR_CERTIFICATE_NUMBER' } | ConvertTo-Json
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/public/certificates/verify' -Method Post -ContentType 'application/json' -Body $body
```

### Verify Certificate by Public Token

```powershell
Invoke-RestMethod -Uri 'http://127.0.0.1:8000/api/v1/public/certificates/verify/YOUR_VERIFICATION_TOKEN'
```

### Verify Certificate PDF

```powershell
curl.exe -X POST http://127.0.0.1:8000/api/v1/public/certificates/verify-pdf -F "certificate_number=YOUR_CERTIFICATE_NUMBER" -F "pdf=@certificate.pdf"
```

## Certificate Verification Flow

1. Admin issues a certificate.
2. The system builds a canonical certificate payload.
3. The payload is hashed using SHA-256.
4. The hash is digitally signed using the active RSA private key.
5. The public verification endpoint checks payload integrity, certificate status, and RSA signature validity.
6. Users can verify the certificate by certificate number, QR/token, or official PDF upload.

## Testing

Run the automated test suite:

```powershell
php artisan test
```

Run static analysis:

```powershell
vendor/bin/phpstan analyse --no-progress
```

Run Laravel Pint:

```powershell
vendor/bin/pint --test
```

For real MySQL concurrency checks, create a separate, empty database named `bbh_farm_concurrency_test`, then run:

```powershell
$env:DB_DATABASE = 'bbh_farm_concurrency_test'
php artisan migrate --force
php tests/Integration/mysql_concurrency_probe.php
```

The probe refuses to run against any other database. It leaves test records in this dedicated database and checks concurrent RSA key deactivation, automatic eartag assignment, and duplicate postnatal care entry.

## Contributing

This project is maintained by the repository owner. Suggestions and issue reports are welcome through GitHub Issues.

## Pre-migration duplicate check

Before applying `2026_09_23_000004_prevent_duplicate_care_records` to an existing database, run `php artisan bbh:check-care-duplicates`. Review and correct any reported duplicates before migrating. The command is read-only and never deletes records.
