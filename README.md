# BBH Farm

BBH Farm is a livestock management system for Bumiku Bumimu Hijau Farm. The application supports dairy goat farm operations, structured livestock records, admin workflows, and electronically verifiable livestock certificates.

## Structure

The system is separated into two Laravel applications:

- `api` handles authentication, master data, livestock operations, certificate issuance, digital signing, verification services, and audit logs.
- `web` provides the admin dashboard, public company pages, certificate previews, and public certificate verification interface.

## Tech Stack

- PHP and Laravel
- Laravel Sanctum
- MySQL or MariaDB
- Blade, Vite, and Tailwind CSS
- OpenSSL RSA-SHA256
- QR-based certificate verification
- PHPUnit and Laravel Pint

## Electronic Certificates

BBH Farm issues electronic livestock certificates with a canonical data payload, SHA-256 hash, and RSA-SHA256 digital signature. Each certificate includes a QR/token reference that can be checked through the public verification page.

The verification flow validates certificate status, signed payload integrity, RSA signature authenticity, and official PDF integrity when a PDF document is uploaded.

## Security

Public verification only exposes controlled certificate information. Admin access is protected through API authentication, while sensitive configuration, local databases, logs, and private keys must remain outside version control.
