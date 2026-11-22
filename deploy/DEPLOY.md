# Deploy hazemelfezary.com — clean redeploy (SSH)

This fixes everything found on the live site: the old code, the broken `/admin`,
the missing pages, the missing images, and the 403 on Arabic URLs.

## The 4 files in this `deploy/` folder — upload these
| File | What it is |
|------|------------|
| `app-code.tar.gz` (1.7 MB) | The full latest application code (no `vendor`, no images). |
| `public-assets.tar.gz` (324 MB) | All images / CSS / JS (`wp-content` + `wp-includes`). Fixes missing images. |
| `hazemelfezary-db.sql` (14 MB) | The complete database — domain already rewritten to `hazemelfezary.com`. |
| `.env.production` | Your production settings (you fill in the DB details). |

---

## 0) Find your app root on the server
SSH in, then:
```
cd ~
find domains -maxdepth 4 -name artisan 2>/dev/null
```
The folder that contains `artisan` is your **APP ROOT** (e.g.
`~/domains/hazemelfezary.com/public_html` or a subfolder). `cd` into it and stay
there for every step. Keep whatever document-root / public mapping you already
have — it works, don't change it.

## 1) Upload the 4 files
Upload all 4 files into the **APP ROOT** (SFTP, or hPanel → File Manager → Upload).

## 2) Extract code + assets
```
tar xzf app-code.tar.gz          # updates the code to the latest version
tar xzf public-assets.tar.gz     # restores every image/asset under public/
```

## 3) Environment file
```
cp .env.production .env
nano .env     # fill DB_DATABASE, DB_USERNAME, DB_PASSWORD (from hPanel → Databases)
php artisan key:generate
```

## 4) Install PHP dependencies
```
composer install --no-dev --optimize-autoloader
```
(If `composer` isn't found, try `php8.3 /usr/local/bin/composer` or `~/composer.phar`.)

## 5) Import the database
```
mysql -u YOUR_DB_USER -p YOUR_DB_NAME < hazemelfezary-db.sql
```
…or import `hazemelfezary-db.sql` through hPanel → phpMyAdmin (select your DB → Import).

## 6) Build + clear caches  ← this is what fixes `/admin`
```
php artisan migrate --force
php artisan storage:link
php artisan filament:assets
php artisan optimize:clear
php artisan config:cache
```
`optimize:clear` removes the **stale old route cache** that was hiding the admin
panel. (If `config:cache` ever errors, just skip it — the site still runs.)

Make sure these stay writable:
```
chmod -R ug+rwX storage bootstrap/cache
```

## 7) Fix the Arabic-URL 403 (ModSecurity)
Arabic article URLs (e.g. `/التدخل-المحدود.../`) return **403** because Hostinger's
ModSecurity blocks the encoded characters. In **hPanel → Security → ModSecurity**
for `hazemelfezary.com`, switch it **Off** (or ask Hostinger support to whitelist
the domain). Re-test an Arabic article afterwards — it should become 200.

## 8) The scheduler (one cron job)
hPanel → **Advanced → Cron Jobs**, add — run **every minute**:
```
* * * * *   cd /FULL/PATH/TO/APP_ROOT && php artisan schedule:run >> /dev/null 2>&1
```
This runs scheduled-article publishing **and** the appointment Telegram bot.

## 9) Verify
Open each — all should work:
- `https://hazemelfezary.com/` , `/blogs/` , `/online-appointment/` , `/cases/` , `/ar/home-ar/`
- `https://hazemelfezary.com/admin` → redirects to the login
- an Arabic article → 200 (after step 7)
- check that images load on a few pages

## Later (optional)
- **Appointments Telegram bot:** create a bot via @BotFather, put `TELEGRAM_BOT_TOKEN`
  + `TELEGRAM_CHAT_ID` in `.env`, then `php artisan config:clear`.
- **Stripe payments:** fill `STRIPE_*` in `.env` when ready.

Admin login (change the password after first login):
`admin@hazemlaravel.test` / `password` — or set a new one in the Users section.
