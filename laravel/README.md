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
The tenancy tests also create `sjpos_phpunit_landlord` and `sjpos_phpunit_tenant_*` databases
themselves (and drop them afterwards), so the test MySQL user needs `CREATE`/`DROP` rights.

```bash
php artisan test
```

## Configuration (`.env`)

| Key | Purpose |
| --- | --- |
| `APP_TIMEZONE` | Defaults to `Asia/Colombo` |
| `OTP_ENABLED` | Default for requiring an emailed login code; each business can change it under **Settings → Sign-in** |
| `OTP_EMAIL` | Default mailbox for codes of users who have no email of their own (also changeable in Settings) |
| `MAIL_MAILER=mailgun`, `MAILGUN_DOMAIN`, `MAILGUN_SECRET`, `MAILGUN_ENDPOINT` | Mailgun sending (`api.eu.mailgun.net` for EU) |
| `TENANCY_ENABLED` | `true` to serve many businesses, each with its own database (see below) |

## Multi-tenancy

With `TENANCY_ENABLED=false` (the default) nothing changes: one business on `DB_DATABASE`.

With it on, the host picks the business: `acme.{TENANCY_BASE_DOMAIN}`, or a custom domain from
the `domains` table. Platform data lives in the landlord database (`LANDLORD_DB_*`); each
business's records live in its own database, which the default `mysql` connection is pointed at
per request. Sessions are tied to the business they started on.

```bash
# Once: create the landlord database, then its tables
php artisan migrate --database=landlord --path=database/migrations/landlord

# A new business (with an owner login)
php artisan tenants:create acme --name="Acme Traders" --owner=admin

# An existing single-business database, registered without copying it
php artisan tenants:register-existing sjtechnology --database=sjpos --name="SJ Technology"

# On every deploy, instead of `php artisan migrate`
php artisan tenants:migrate

# Any command, once per business
php artisan tenants:run "sjpos:user admin --password=..." --tenant=acme
```

Business databases use the normal migrations in `database/migrations`; the landlord's are in
`database/migrations/landlord`.

Each business keeps its files under `storage/app/tenants/{id}/`: `branding/` (uploaded logo, served at
`/logo`), `backups/`, and `private/` (the default `local` disk, e.g. archived quotation PDFs). Without
tenancy the same folders sit directly under `storage/app/`. Logos uploaded before this change stay in
`public/uploads` and keep working until replaced.

Login codes go to each user's own email (set on the Users page), or to the business's fallback mailbox.
Emails are sent from `MAIL_FROM_ADDRESS` under the business's name, with replies going to the business.

## Moving an existing install onto tenancy (SJ Technology)

The existing database becomes the first business as it is: nothing is copied, and every step below
only adds (tables, settings, copied files), so the old setup can be switched back on at any point.

```bash
php artisan tenants:register-existing sjtechnology --database=<existing db> --name="SJ Technology"
php artisan tenants:import-files sjtechnology --dry-run   # list what will be copied
php artisan tenants:import-files sjtechnology             # logo, backups, stored files, sign-in settings
php artisan tenants:add-domain sjtechnology <old address, e.g. pos.sjtechnology.lk> --primary
php artisan tenants:verify sjtechnology                   # must end with "No failures."
```

`tenants:verify` checks migrations, business details, logo, owners, sign-in codes, payment and stock
consistency, and that an invoice PDF renders. With `--compare=<original db>` (for a move that restores a
dump into a new database) every table's row count and the money totals must match the original.
`WARN` lines are worth reading but don't block the move; `FAIL` lines do.

**Practice run on staging first**

1. Staging must run PHP 8.4+ (`php -v`), with `pdo_mysql`, `gd` and `zip`.
2. Restore the latest production backup into a new database, e.g. `sjpos_rehearsal`.
3. Deploy this branch; in `.env` set `TENANCY_ENABLED=true`, `TENANCY_BASE_DOMAIN`,
   `TENANCY_CENTRAL_DOMAINS` and `LANDLORD_DB_*`; then `php artisan config:clear`.
4. `php artisan migrate --database=landlord --path=database/migrations/landlord`.
5. Run the five commands above against `sjpos_rehearsal`.
6. Sign in as an owner, a manager and a staff user; open, create and email an invoice; record and
   delete a payment; check reports and Settings. Note anything that looks wrong.

**Cutover (after closing time, about 30 minutes)**

1. Switch off the legacy plain-PHP app (or make it read-only): it writes to the same database but skips
   the stock, tax and payment rules.
2. `php artisan down`, then take a backup (`php artisan sjpos:backup`) and copy it off the server.
3. Deploy, set the tenancy `.env` keys as on staging, `php artisan config:clear`, create the landlord tables.
4. Run the five commands above against the production database.
5. DNS: point the business subdomain (and keep the old address) at the server.
6. `php artisan up`. Sign in, record a test payment and delete it again.

**Rolling back:** set `TENANCY_ENABLED=false` and run `php artisan config:clear`. The app is back to one
business on `DB_DATABASE`. Its backups in `storage/app/backups` were only copied, and the imported logo
and sign-in settings work the same without tenancy. Keep the pre-cutover backup and the old app's files
for two weeks.

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
