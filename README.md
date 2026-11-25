# Urukundo — Rwanda Wedding Venue Booking

**Student:** MUKANTWARI ANITHA · **Registration number:** 224017252

A professional responsive PHP/MySQL venue-booking project. The prepared couple account can discover wedding
venues, manage its couple profile, request a date and manage
their bookings. A venue manager maintains venue listings and booking status.
The interface uses a consistent shared header, responsive content sections,
and footer with a navy-and-teal visual theme.

## Requirements

- PHP 8.1+ with PDO MySQL and `mbstring`
- MySQL 8+ or MariaDB 10.5+
- A web browser with JavaScript enabled
- Vanilla PHP, HTML, CSS and JavaScript; no framework or external UI library

## Setup

1. Start Apache and MySQL/MariaDB from XAMPP Control Panel.
2. If this is a fresh database, import `database/schema.sql` followed by
   `database/seed.sql` using your MySQL/MariaDB client. The seed can be run
   again without duplicating the prepared accounts, venues, or bookings.
3. Open `http://localhost/online-wedding-venue-booking-project/public/` in a
   browser.

The app connects to host `127.0.0.1`, port `3306`, database
`wedding_venue_booking`, user `root`, and an empty password by default. To use
different connection settings, set `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
and `DB_PASS` before starting the app. Keep local database credentials private
and change them before deployment.

## Prepared local accounts

The project uses two account roles. The seed includes two main demo accounts
plus two additional couple accounts for testing; every seeded account uses
password `password`.
The listed Gmail addresses are identifiers for local sign-in and are not
configured to receive email.

| Role | Email | Password |
|---|---|---|
| Venue manager | `anitahvenue@gmail.com` | `password` |
| Couple | `Girlbertfamily@gmail.com` | `password` |

All venue prices and deposits are shown in Rwandan francs (RWF). The Ndayisaba
family account has a couple profile and two prepared bookings. Public couple
registration is enabled. Change the prepared account passwords before deployment.
Deposit recording is for coursework only;
it does not contact a payment provider or transfer money. The seed script can
be run again without duplicating the prepared accounts or bookings.

## Routes

| Route | Purpose |
|---|---|
| `/` | Home and featured venues |
| `/venues` | Venue catalogue and search |
| `/venue/form` | Create venue (venue manager) |
| `/venue/form?id=1` | Edit venue (venue manager) |
| `/venue/save`, `/venue/delete` | CSRF-protected venue mutations |
| `/login`, `/login/process`, `/logout` | Session authentication |
| `/bookings` | Current customer's bookings or venue manager booking list |
| `/my-weddings` | Customer wedding dashboard |
| `/my-weddings/budget` | CSRF-protected budget update |
| `/booking/form`, `/booking/form?id=1` | Create/edit booking |
| `/booking/save`, `/booking/delete`, `/booking/pay` | CSRF-protected booking and deposit-record actions |
| `/api/venues?q=Kigali` | JSON venue search for Fetch/AJAX |

Routes are handled through `public/index.php` with a `_route` query parameter,
so Apache rewrite rules and an `.htaccess` file are not required. Use the
navigation links in the app; for example, the venue list is
`public/index.php?_route=%2Fvenues`, and venue search is
`public/index.php?_route=%2Fapi%2Fvenues&q=Kigali`.

## Data model

`customers` stores login identity, partner/contact details and budget data.
`venues` holds venue information. `bookings` references a venue, customer and
venue manager. `payments` stores payment records and `reviews` stores customer
feedback. See `database/schema.sql` for keys, indexes and constraints.

## Security and behavior

- Passwords use `password_hash()` / `password_verify()`.
- All SQL value input uses PDO prepared statements with native prepares.
- Output is escaped with `htmlspecialchars()`.
- Every state-changing form uses a session CSRF token; deletes are POST-only.
- Server-side validation complements browser-side validation.
- The two supported roles are `customer` and `manager`; customer bookings are scoped
  to the signed-in customer, while venue management is manager-only.
- Venue/date conflicts are checked inside a transaction while locking the venue.
- Session cookies use `HttpOnly` and `SameSite=Lax`; baseline security headers
  are set. Uncaught errors are logged to `logs/app.log` outside `public/`.

## Tests and live presentation

The 27-case test plan is in [`docs/TESTING.md`](docs/TESTING.md). Run
[`tests/TestPlan.php`](tests/TestPlan.php) to exercise the routes, authentication,
authorization, validation, booking, deposit, and CSRF flows against your local
database. The runner creates temporary test records and removes them before it
exits. It prints a PASS/FAIL line for every case and returns a non-zero exit code
if setup fails or any case fails. Keep the final console output as test evidence
for submission; only report results from a run you actually completed.

The test plan also includes a 2–3 minute live-presentation sequence. Record the
presentation locally if a video submission is required.

### Run the executable test plan

Import the schema and seed data, start MySQL/MariaDB, then open PowerShell in
the project folder and run:

```powershell
cd "C:\xampp\htdocs\online-wedding-venue-booking-project" then
& "C:\xampp\php\php.exe" ".\tests\TestPlan.php"
```

The runner starts a temporary PHP server on an available local port, executes
27 database, page, API, registration, login, authorization, booking, deposit,
and CSRF checks, then prints PASS/FAIL evidence. It requires the project
database imported from `database/schema.sql` and `database/seed.sql`, and uses
the DB environment variables documented above when set.

## Submission checklist

- [ ] Create/push GitHub repository `BIT-Y3-G1-224017252-WeddingVenueBooking`
- [ ] Add `bemmy2005` as a collaborator
- [ ] Submit repository URL and summary on the LMS
- [ ] Record the live presentation and capture the test evidence required by the instructor
