# Test plan and evidence

## Automated integration test plan

The runner executes all 27 cases below against the local MySQL database and a
temporary PHP development server. It uses disposable customer/booking records
and removes them at the end. Run it from PowerShell in the project directory:

```powershell
cd "C:\xampp\htdocs\online-wedding-venue-booking-project" then
& "C:\xampp\php\php.exe" ".\tests\TestPlan.php"
```

Before running:

1. Start MySQL/MariaDB in XAMPP.
2. Import `database/schema.sql` and `database/seed.sql` if the database is not
   prepared yet.
3. Ensure the prepared accounts and at least one available venue are present.
   The runner uses `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` if
   set; otherwise it uses the defaults in `src/Config/Database.php`.
4. Ensure the PHP CLI has PDO MySQL and `mbstring` enabled.

| # | Path | Test | Expected result |
|---|---|---|---|
| 1 | Happy | Open the home route. | HTTP 200 and the Urukundo page renders. |
| 2 | Happy | Inspect response security headers. | `X-Content-Type-Options: nosniff` is present. |
| 3 | Happy | Open the venue catalogue. | HTTP 200 and seeded venue data renders. |
| 4 | Happy | Search the venue API for Kigali. | HTTP 200 with a valid JSON success payload and results. |
| 5 | Failure | Search for a venue name that does not exist. | HTTP 200 with an empty result list. |
| 6 | Failure | Open bookings without signing in. | HTTP 303 redirects to sign-in. |
| 7 | Failure | Submit a registration with invalid email and short/mismatched password. | Request redirects back and validation errors display. |
| 8 | Failure | Register using the prepared couple's email. | Duplicate is rejected and the existing account count remains one. |
| 9 | Happy | Register with valid unique test details. | A customer is created with a verifiable password hash. |
| 10 | Failure | Sign in with an incorrect password. | Request redirects and shows a generic credential error. |
| 11 | Happy | Sign in as the prepared couple. | Request succeeds and redirects to the app. |
| 12 | Happy | Open My Weddings as the couple. | Dashboard and budget render successfully. |
| 13 | Happy | Open the couple's booking list. | HTTP 200 and the customer booking view renders. |
| 14 | Failure | Open venue management as a couple. | HTTP 403. |
| 15 | Failure | Submit a past event date. | Request redirects back with date validation error; no booking is created. |
| 16 | Failure | Request a date already booked at that venue. | Request redirects back with a date-conflict error. |
| 17 | Failure | Open another customer's booking by its ID. | HTTP 404; the other booking is not exposed. |
| 18 | Happy | Request a free future venue date. | A new booking is persisted with pending status. |
| 19 | Happy | Record the test booking's deposit. | One completed deposit record equals 20% of the venue price. |
| 20 | Failure | Record the same deposit again. | No duplicate completed payment is created. |
| 21 | Happy | Delete the test booking as its owner. | Booking is removed; its dependent test payment is cleaned up. |
| 22 | Failure | Submit a booking mutation without a CSRF token. | HTTP 419 and no change is made. |
| 23 | Happy | Sign in as the prepared venue manager and open venue form. | Login redirects and venue form returns HTTP 200. |
| 24 | Failure | Try to create a couple booking as the manager. | HTTP 403. |
| 25 | Happy | Sign out with a valid CSRF token. | Session is invalidated and the next protected request redirects. |
| 26 | Failure | Sign out without a CSRF token. | HTTP 419. |
| 27 | Failure | Submit a valid registration from a fresh session without first loading the form. | HTTP 419 and no customer account is created. |

### Evidence to retain

The runner prints one `[PASS]` or `[FAIL]` line per case and a final summary.
Save or capture that actual console output for submission. A non-zero exit code
means setup failed or a test failed; correct the issue and rerun. Do not claim a
passing run based only on the expected results in this document.

## Additional manual checks

Run these through a browser after the automated plan. Use a fresh test venue or
booking where appropriate, and remove test data afterward.

| # | Test | Expected result |
|---|---|---|
| M1 | Search venues using the page's live-search input. | Results update without a page reload; an unmatched term shows the empty state. |
| M2 | Sign in as manager; create, edit, then delete an unused venue. | Each change persists and the updated catalogue is shown. |
| M3 | Try deleting a venue referenced by a booking. | Venue remains and the app shows a failure message. |
| M4 | Update the couple's budget with valid values, then try spending above the total. | Valid budget is saved; invalid values are rejected. |
| M5 | Run `database/seed.sql` twice and inspect prepared accounts/venues/bookings. | Prepared records remain usable and no duplicate seeded records appear. |
| M6 | Resize the browser to a narrow/mobile viewport. | Navigation, forms, venue cards, and dashboard remain readable and usable. |

## 2–3 minute live demonstration

1. Browse the Rwandan venue catalogue and demonstrate live search.
2. Sign in as the prepared couple and show My Weddings and the booking list.
3. Request an available future date; explain the pending status and 20% deposit.
4. Sign in as the venue manager and show venue management and booking visibility.
5. Point out that the deposit action records coursework data only and does not
   transfer money.

Record the demonstration locally if a video is required. The demo video is a
separate submission deliverable and is not generated by the PHP test runner.
