# BBH Farm

BBH Farm is a livestock management platform for Bumiku Bumimu Hijau Farm, built to support dairy goat operations, digital certificate issuance, and RSA-SHA256 certificate verification.

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

- Backend: PHP, Laravel, Laravel Sanctum
- Frontend: Laravel Blade, Vite, Tailwind CSS, Open Sans
- Database: MySQL/MariaDB
- Certificate Security: OpenSSL, RSA-SHA256, QR/token verification
- Documentation and Quality: OpenAPI, PHPUnit, PHPStan/Larastan, Laravel Pint

## Project Structure

```text
bbh-farm/
|-- api/
|-- web/
`-- README.md
```

The `api` application handles authentication, business rules, livestock data, certificate signing, and verification services. The `web` application provides the admin interface, public pages, and certificate verification experience.

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
