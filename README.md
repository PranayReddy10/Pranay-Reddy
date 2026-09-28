# DevLedger

A private work and finance tracker for a freelance developer and marketer. It's a Laravel 13 app you can install as a PWA.

It keeps everything in one place:

- **Projects**: client websites, your own projects and ad/content sites. You can set a one-time build fee, a recurring bill (monthly, quarterly, half-yearly or yearly) and an optional ad-revenue split with a partner.
- **Clients**: who pays you, what they've paid so far, and their recurring value.
- **Partners**: friends who get a share of ad revenue on some projects. The app splits the money automatically and tracks what you still owe them.
- **Accounts**: every registrar, hosting, ad-network or payment login. Domains and servers can sit in different accounts. The app does not store passwords.
- **Domains**: which account holds each domain, which server it's hosted on, when it expires, what renewal costs, and whether you or the client pays.
- **Hosting / servers**: each server's account, billing cycle, cost and next due date.
- **Transactions**: every rupee in or out, linked to a project, client, account, domain, server or partner. You can export them to CSV.
- **Renewals & dues**: domain expiries, hosting bills and client billing on one timeline. One tap marks an item paid, renewed or received. That books the transaction and moves the due date forward.
- **Spending analysis**: month-by-month income against spending, spending and income by category, profit per project, spending per account, a year-on-year comparison and your yearly recurring run-rate.

How the money is counted:

- **My income** = income received minus the partner's share.
- **Spending** = all expenses except partner payouts. A payout only settles a share that was already taken out of your income.
- **Net** = my income minus spending.

## Setup

Requirements: PHP 8.3+ with `pdo_sqlite` (or MySQL), and Composer. You don't need Node or a build step. The CSS and JS are plain files in `public/`.

```bash
cp .env.example .env            # set OWNER_EMAIL / OWNER_PASSWORD (and DB_* if using MySQL)
composer setup                  # install, key, sqlite file, migrate, create owner login
php artisan serve
```

- Explore with sample data: `php artisan db:seed --class=DemoSeeder`
- Create the login or reset its password at any time: `php artisan ledger:owner you@example.com`
- Run the tests: `php artisan test`

Settings in `.env`:

| Key | Default | Meaning |
| --- | --- | --- |
| `LEDGER_CURRENCY` | `₹` | Currency symbol |
| `LEDGER_DUE_SOON_DAYS` | `30` | Window for the dashboard's "due soon" list |

Categories, billing cycles, account types and payment methods are in `config/ledger.php`.

## PWA

- The app ships `public/manifest.webmanifest`, `public/sw.js` and icons in `public/icons/`.
- Serve it over HTTPS at the root of a (sub)domain, for example `ledger.yourdomain.com`. The browser will then offer **Install**.
- Pages you've opened stay readable offline.
- Signing out clears the pages cached on the device.
- Home-screen shortcuts: *Add expense*, *Add income* and *Renewals & dues*.

## Deploying on shared hosting / VPS

1. Point the document root to `public/`.
2. Set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=https://…`.
3. Run `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan ledger:owner`.
4. Run `php artisan config:cache route:cache view:cache`.

Search engines are told not to index the site (`robots.txt` and a `noindex` meta tag), and every page except login and offline needs you to sign in.
