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
- **Bills (invoices)**: itemised bills per project, with discount and optional GST/tax. The app shows what's billed, what the client paid and the balance, with Paid, Part-paid and Overdue status.
  - **Final bill** on a project pre-fills the agreed amount. Advances you already recorded can be linked to it, so the bill shows them as paid.
  - Record part payments as they come in.
  - **Share**: a secret link the client opens without logging in, sent by WhatsApp, email, copy link or the phone's share sheet. You can reset the link at any time.
  - **PDF**: print or save any bill as an A4 PDF. Bills include a UPI QR code and a "Pay via UPI" button when your UPI ID is set.
  - Your business name, contact details, UPI ID, bank details, GSTIN and bill-number prefix are set under **Settings**.
- **Personal spending**: day-to-day expenses (food, rent, travel, EMI…), kept completely separate from the business books.
  - Monthly budgets per category.
  - Month-by-month view with what you've spent against what work earned, plus how much you saved.
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

## Deploying on Hostinger (shared hosting)

Keep the project **outside** `public_html`. Only its `public/` folder may be reachable from the web, otherwise `.env` and the SQLite database can be downloaded. Serve the app from its own subdomain, because the PWA needs to sit at the root of a domain.

```bash
# 1. Move the project out of the web folder
cd ~/domains/madeforu.co.in
mv public_html/Pranay-Reddy ./Pranay-Reddy

# 2. In hPanel → Domains → Subdomains, create e.g. "ledger" (folder: public_html/ledger)
#    then swap that folder for a link to the app's public/ directory:
ls -la public_html/ledger          # should be empty or only Hostinger's default page
rm -rf public_html/ledger
ln -s ~/domains/madeforu.co.in/Pranay-Reddy/public public_html/ledger

# 3. Configure and cache
cd ~/domains/madeforu.co.in/Pranay-Reddy
nano .env                          # APP_URL=https://ledger.madeforu.co.in, APP_ENV=production, APP_DEBUG=false
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Turn on SSL for the subdomain in hPanel → Security → SSL. In hPanel → Advanced → PHP Configuration, set PHP to 8.3 or newer.

To update later: `git pull origin main`, then `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan config:cache`.

## Deploying on shared hosting / VPS

1. Point the document root to `public/`.
2. Set `APP_ENV=production`, `APP_DEBUG=false` and `APP_URL=https://…`.
3. Run `composer install --no-dev -o`, `php artisan migrate --force` and `php artisan ledger:owner`.
4. Run `php artisan config:cache route:cache view:cache`.

Search engines are told not to index the site (`robots.txt` and a `noindex` meta tag), and every page except login and offline needs you to sign in.
