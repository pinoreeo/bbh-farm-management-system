# BBH Farm

Laravel-based livestock management platform for BBH Farm, covering dairy goat operations, digital certificate issuance, and RSA-SHA256 certificate verification.

## Main Features

### Livestock Management

- Animal identity and breed management
- Colony pen management
- Animal movement records
- Weight records
- Breeding period and breeding female records
- Pregnancy check records
- Birth event and offspring birth records
- Postnatal care records
- Health treatment records
- Vaccination records

### Digital Certificate Management

- Digital certificate issuance for livestock records
- Certificate type management
- RSA-SHA256 digital signing
- RSA key generation and activation
- Certificate revocation and reactivation
- Certificate preview and PDF export
- QR/token-based certificate verification

### Public Verification

- Certificate verification by certificate number
- Certificate verification by QR/token
- Official certificate PDF integrity verification
- Public verification result page
- Public verification logs
- Rate-limited public verification flow

### Admin and Audit

- Admin dashboard
- User management
- Admin-only protected resources
- Admin activity logging
- Operational reports
- Structured validation and error handling

## Tech Stack

### API

- PHP 8.2+
- Laravel 11
- Laravel Sanctum
- MySQL/MariaDB
- OpenSSL
- Simple QrCode
- L5 Swagger / OpenAPI
- PHPUnit
- PHPStan / Larastan
- Laravel Pint

### Web

- PHP 8.3+
- Laravel 13
- Vite
- Tailwind CSS
- Blade templates
- Open Sans

## Project Structure

```text
bbh-farm-v3/
|-- api/
|   |-- app/
|   |-- config/
|   |-- database/
|   |-- public/
|   |-- resources/
|   |-- routes/
|   `-- tests/
|
|-- web/
|   |-- app/
|   |-- config/
|   |-- database/
|   |-- public/
|   |-- resources/
|   |-- routes/
|   `-- tests/
|
`-- README.md
```

Important directories:

- `api/app/Http/Controllers/Api/V1` contains API controllers.
- `api/app/Models` contains core Eloquent models.
- `api/app/Services` contains certificate signing, verification, PDF integrity, and activity logging logic.
- `api/database/migrations` contains database schema definitions.
- `api/database/seeders` contains initial and demo data seeders.
- `api/resources/views/certificates` contains certificate templates.
- `web/app/Http/Controllers` contains web-facing controllers.
- `web/resources/views` contains admin and public Blade views.
- `web/resources/css` and `web/resources/js` contain frontend assets.
- `web/routes/web.php` contains public and admin web routes.

## Certificate Verification Flow

1. Admin issues a certificate from the dashboard.
2. The system builds a canonical certificate payload.
3. The payload is hashed using SHA-256.
4. The hash is signed using the active RSA private key.
5. A QR/token verification reference is attached to the certificate.
6. Public users can verify the certificate by number, QR/token, or official PDF upload.
7. The system validates certificate status, payload integrity, and RSA signature authenticity.

## Security Notes

- Public registration is not available.
- User accounts are managed by authorized admins.
- Public certificate verification exposes only controlled certificate data.
- Sensitive files such as `.env`, local databases, logs, and private keys must not be committed.
- Production environments should use HTTPS, strong credentials, and disabled debug mode.
