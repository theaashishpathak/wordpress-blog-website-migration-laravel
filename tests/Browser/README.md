# Browser tests

Pest 4 browser tests covering NewsPilot's critical user journeys plus a
full-suite smoke pass. Excluded from the default `php artisan test` run
because they require the optional Pest Browser plugin + a Chromium
binary.

## What's here

| File | Coverage |
| --- | --- |
| `SmokeTest.php` | Hits every primary route (admin + frontend) as guest, super admin, author, editor and ad manager. Asserts HTTP 200 + no JS console errors. |
| `Auth/AuthFlowTest.php` | Fortify login, registration, password reset, logout. |
| `Admin/PostCreateAndPublishTest.php` | Author composes a post, saves draft, publishes. AI assist mocked. |
| `Admin/EditorialWorkflowTest.php` | Author submits → editor requests changes → approves → publishes. |
| `Admin/AdMonetizationTest.php` | Admin creates ad zone + creative; visitor sees ad; click bumps counter. |
| `Frontend/ReadingAndCommentTest.php` | Home → category → post → comment → admin moderates. |
| `Frontend/NewsletterSignupTest.php` | Signup → confirm token → unsubscribe → honeypot. |
| `Frontend/LocaleSwitchTest.php` | EN default, `/bn` prefix renders Bangla translation. |

## Setup (one-time)

```bash
composer require pestphp/pest-plugin-browser:^4.0 --dev
npm install playwright@latest
npx playwright install
```

The Playwright install downloads a Chromium binary (~200 MB) and caches
it under `~/.cache/ms-playwright/`.

## Running

```bash
# Browser suite only
vendor/bin/pest tests/Browser

# A single file
vendor/bin/pest tests/Browser/SmokeTest.php

# Filter by name
vendor/bin/pest tests/Browser --filter='admin can load every admin route'
```

To run them as part of the default `php artisan test` invocation,
re-enable the `Browser` testsuite in `phpunit.xml` by adding:

```xml
<testsuite name="Browser">
    <directory>tests/Browser</directory>
</testsuite>
```

## Notes

* Tests use `LazilyRefreshDatabase` so the schema is rebuilt only when a
  test actually touches the database.
* `Pest.php` automatically marks every browser test as skipped if the
  plugin isn't detected, so the suite never hard-fails on a machine
  that hasn't run the setup.
* The AI Manager is mocked in `Admin/PostCreateAndPublishTest.php` —
  these tests will never hit OpenAI or Gemini.
* Some selectors target Tailwind class hashes (`wire\:model\.live...`).
  If a UI refactor changes those, prefer adding a stable
  `data-test="..."` hook to the blade rather than chasing the selector.
