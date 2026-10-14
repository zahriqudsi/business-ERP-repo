# SJ Technology POS — Laravel

Laravel 13 port of the legacy plain-PHP invoice system that lives in the parent folder.
It runs on the **same MySQL database and schema**, so existing data (invoices, quotations,
payments, job notes, users and their passwords) works without any conversion.

## What's where

| Legacy file(s) | Laravel |
| --- | --- |
| `login.php`, `verify-otp.php`, `logout.php`, `includes/auth.php` | `Auth\LoginController`, `Auth\OtpController`, `auth` middleware |
| `includes/mailer.php` | `App\Services\LoginOtp`, `App\Mail\LoginOtpMail` (Laravel mail / Mailgun driver) |
| `index.php` | `DashboardController`, `resources/views/dashboard.blade.php` |
| `invoices/*`, `generate_pdf.php` | `InvoiceController`, `views/invoices/*`, `views/pdf/invoice.blade.php` |
| `invoices/outstanding_ar.php` | `OutstandingArController`, `views/outstanding-ar/*` |
| `quotations/*`, `generate_quotation_pdf.php` | `QuotationController`, `views/quotations/*`, `views/pdf/quotation.blade.php` |
| `payments/*` | `PaymentController`, `views/payments/*` |
| `purchases/*` | `PurchaseController`, `views/purchases/*` |
| `bills/*` | `VendorBillController`, `views/bills/*` |
| `jobs/*` | `JobNoteController`, `views/jobs/*`, `views/pdf/job-note.blade.php` |
| `includes/header.php` / `footer.php` | `views/layouts/app.blade.php` |
| `create.php`, `create_user.php`, `hash.php` | `php artisan sjpos:user <username>` |
| Company/bank details, default terms (hard-coded) | `config/sjpos.php` |

Old URLs such as `/invoices/view_invoice.php?id=5` or `/generate_pdf.php?id=5` redirect to the new routes,
so bookmarks keep working.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Set the `DB_*` values in `.env` (default database: `sjpos`), then:

```bash
php artisan migrate
php artisan sjpos:user admin
php artisan serve
```

`migrate` is safe on the existing database: every table is guarded with `hasTable()`, so it only
records the migration. On an empty database it builds the full schema.

PDFs need the PHP **GD** extension (Dompdf uses it for the PNG logo and signatures).

## Tests

The tests use a separate MySQL database `sjpos_phpunit` (see `phpunit.xml`) because the app uses
MySQL-specific SQL (`DATEDIFF`, `FIELD`, `CAST … AS UNSIGNED`).

```bash
php artisan test
```

## Configuration (`.env`)

| Key | Purpose |
| --- | --- |
| `APP_TIMEZONE` | Defaults to `Asia/Colombo` |
| `OTP_ENABLED` | `true` to require the emailed login code (was the `OTP_ENABLED` constant) |
| `OTP_EMAIL` | Mailbox that receives login codes |
| `MAIL_MAILER=mailgun`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` | Mailgun sending (`api.eu.mailgun.net` for EU) |

## Deploying

1. Point the web server's document root at `laravel/public` (not the repo root).
2. On the server: `composer install --no-dev --optimize-autoloader` inside `laravel/`, create `.env`
   (`APP_ENV=production`, `APP_DEBUG=false`, DB and mail settings), `php artisan key:generate`.
3. `php artisan migrate --force`, then `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
4. `storage/` and `bootstrap/cache/` must be writable by the web server user.

The GitHub deploy workflows in `.github/workflows` still deploy the legacy app; update their scripts
(`cd laravel`, run the steps above) when switching over.

## Behaviour changes from the legacy code

- **Payment status bug fixed.** The old SQL `SET amount_paid = …, status = CASE WHEN amount_paid + ? >= subtotal`
  read the *already-updated* `amount_paid`, so an invoice was marked *paid* after only half was received.
  Payments now go through `TracksPayments::applyPayment()`, which clamps and sets the status correctly.
- **Next invoice number** is computed numerically; `MAX(invoice_no)` on the varchar column compared text.
- **Totals are recalculated on the server** from quantity × unit price instead of trusting the posted values.
- **CSRF protection** on every form/AJAX call, **login throttling**, and logout is a POST.
- Quotation fields are stored as typed; the old code stored HTML-escaped text (e.g. `H&amp;R Colombo`).
- The invoice PDF prints the invoice's own terms (previously a fixed list).
- Quotation → invoice conversion asks for confirmation and runs in a transaction.
- Mailgun credentials come from `.env` instead of source code.
- `invoices/convert_to_invoice.php` (an unlinked, half-finished page) and `mailgun_test.php` were not ported.
