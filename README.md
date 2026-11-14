# Brew & Bloom — Online Ordering System

Lightweight online ordering app for a milk tea & coffee shop. Vanilla PHP 8.2+, PDO, MariaDB/MySQL, no frameworks.

## Features
- Customer storefront: category filters, cart (localStorage), pickup/delivery checkout, order tracking.
- Admin panel: product & category management with image uploads, order workflow, live pending-order badge with sound alert, sales summary, store open/close switch, SQL backup download.

## Structure
```
config/      env + PDO singleton (env.php is git-ignored; copy env.example.php)
src/         Helpers (Security, Auth, Uploader), Models, Services (OrderService)
templates/   Shared admin layout
public/      Web root: storefront, assets, admin/
database/    schema.sql, seed.sql, create_admin.php (CLI)
storage/     logs (not web accessible)
```

## Security Controls
- PDO prepared statements everywhere (emulation off); output escaped via `e()`.
- CSRF tokens on every state-changing form, verified with `hash_equals()`.
- Secure sessions: strict mode, HttpOnly, SameSite=Strict, Secure on HTTPS, ID regeneration on login.
- Generic login errors, `password_hash()` / `password_verify()`, active-user recheck on each admin request.
- Uploads validated by real MIME type (`finfo`), 2 MB cap, random filenames, PHP execution blocked in uploads.
- IDOR protection: customers can view an order only with matching order code **and** phone number.
- Server-side price recalculation; orders and items written in one atomic transaction with status-transition rules.
- `.htaccess` blocks direct access to `config/`, `src/`, `templates/`, `database/`, `storage/`.
- Backups exclude the `users` table.

## Local Setup (XAMPP)
1. Place the project in `htdocs/brew-and-bloom-app` and start Apache + MySQL.
2. Import `database/schema.sql`, then `database/seed.sql` (phpMyAdmin or `mysql < file`).
3. `copy config\env.example.php config\env.php` and set DB credentials and `BASE_URL`.
4. Create the admin account: `php database/create_admin.php` — then change the default password immediately.
5. Open `http://localhost/brew-and-bloom-app/` (store) and `/admin/login.php` (admin).

## Production Notes
- Set `APP_ENV` to `'production'` in `config/env.php`, serve over HTTPS, use a dedicated DB user with minimal privileges.