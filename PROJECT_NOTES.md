# Prof. Dr. Hazem El Fezary — Laravel replica of `hazembefore` (WordPress)

A Laravel 13 + Filament application that reproduces the bilingual (English +
Arabic/Polylang) WordPress site **`hazembefore`** — the complete, up-to-date
source (all page/article/image edits), captured in full — (Elementor Pro, theme
**hello-elementor**) with a MySQL backend, an admin dashboard, a Yoast-style SEO
module, Google integration and a redirect manager.

Content: **63 pages, 126 articles, 68 medical FAQ, 4 cases** (+ archives), in
English and Arabic. (An earlier build captured the older `hazemfezary` copy,
which was missing most of this content.)

Built with the exact method used for the `chem` replica, adapted for this site's
two big differences: **hierarchical page URLs** and **two languages**.

## URLs
- Front-end: http://hazemlaravel.test
- Admin dashboard: http://hazemlaravel.test/admin

## Default accounts (change these)
| Role | Email | Password |
|------|-------|----------|
| Admin | admin@hazemlaravel.test | password |
| Marketing Manager | marketing@hazemlaravel.test | password |

## Zero-404 guarantee (the whole point)
Every original WordPress URL resolves 1:1. The source site's slugs are preserved
**exactly** because we store each captured page under its **full relative path**
(hierarchical, per-language) and serve it through a single catch-all route:

- English pages keep their full hierarchy — e.g.
  `/services/spine-deformity-correction/scoliosis-correction/`
- Arabic pages keep the Polylang `/ar/...` prefix — e.g. `/ar/home-ar/`
- Posts/cases/medical_faq keep their exact (often percent-encoded Arabic) slugs
- WordPress' automatic canonical redirects (bare slug → full path) are
  reproduced as 301s so old/short links never 404
- Trailing slashes are preserved (served `200`, never stripped) and HTTPS is not
  forced — matching the source exactly (see `public/.htaccess`)

**Verification:** every URL in the original Yoast sitemap (100 URLs) was crawled
on both sites. Result: `BOTH_200=92, BOTH_NON200=8, REGRESSIONS=0`. The 8
non-200s already 404 on the original WordPress site (orphaned Arabic
`medical_faq` singles + `faq-topic` archives), so there are **zero new 404s**.

## Architecture
- **Captured pages** — the fully-rendered HTML of the static public URLs (63
  pages, 4 cases, medical_faq, taxonomy/author archives) was captured from the
  live WordPress render and stored in the `pages` table (`path`, `language`,
  `head_html`, `content_html`, `body_class`, `html_lang`, `html_dir`, `wp_*`).
  Served verbatim through `resources/views/captured.blade.php` → pixel-faithful,
  per-language (RTL Arabic intact).
- **Articles are a living, dynamic blog (not frozen captures)** — all articles
  live in the native `posts` table and render dynamically, because a captured
  snapshot can't list new posts, can't schedule, and omits not-yet-public
  (scheduled) articles. `php artisan wp:import-articles` imports them straight
  from the WordPress source DB:
  - **126 published** posts → `status=published`, rendered at their EXACT
    original permalink (path taken from `urls.json`, the same inventory that
    generated the sitemap → zero-404) via `PageController`, which falls back to
    a published `Post` when a path isn't a captured page.
  - **14 scheduled** (WP `future`) posts → `status=scheduled` with their future
    `published_at`. They show in the admin **Schedule Articles** page, stay 404
    until their date (exactly like WP), and auto-publish via
    `articles:publish-due` / the `PublishScheduledArticles` middleware.
  - Yoast SEO (title/description/focus keyphrase/OG/Twitter/schema) is copied
    into `seo_meta`; featured images resolve to `/wp-content/uploads/...`.
  - The archive lives at **`/blogs`** (+ `/ar/blogs`) via `BlogController`;
    single articles use the AALTO design — a **blurred-thumbnail hero** behind
    the title + a two-column layout with a real-data sidebar (search, recent
    articles, contact). Direction follows the actual text (some Arabic articles
    sit in the no-prefix/English Polylang tree but still render RTL).
  - Dynamic pages wear the site's real theme chrome via `App\Support\SiteShell`,
    which extracts the captured home page's head/header/footer per language and
    injects per-article SEO — so a dynamic article looks identical to a captured
    page even though the `settings` header/footer HTML was never seeded.
- **Admin sections by content type**: captured content (Pages 63, Medical FAQ,
  Cases 4) is split in the Filament admin via thin models (`Faq`, `CaseStudy`)
  that share the `pages` table with a `wp_type` global scope; **Articles** (126)
  and **Schedule Articles** (CSV publisher) are the native `Post` model. Plus
  **SEO**, **Google & Marketing**, **Redirects**, **Site Settings** and
  **Users**. Unused chem resources (Services/Portfolios/Team) are hidden.
- **SEO is preserved automatically**: the captured `<head>` already contains the
  original Yoast title/description/canonical/OG/Twitter/JSON-LD tags, so
  front-end SEO is identical to the source, byte-for-byte (minus the host
  rewrite). The Filament SEO module (`seo_meta`, `App\Support\Seo`,
  `SeoAnalyzer`) is available for editing.
- **Google**: the source has Google Site Kit installed but **no active GA4 / GTM
  / Search Console tags were emitted** (dev-settings stub), so there was nothing
  to migrate. Admin → *Google & Marketing* is ready for real tags, injected via
  `Seo::googleTags()`.
- **Theme assets** (uploads, Elementor/Elementor-Pro/hello-elementor/polylang/
  hazem-* CSS·JS·fonts·images) were copied — **static files only, never .php** —
  into `public/wp-content` and `public/wp-includes`, so the original markup
  resolves unchanged.
- **Redirects**: polymorphic `redirects` table + `HandleRedirects` middleware
  (global, catches would-be 404s). Populated with the WordPress canonical
  redirects. Yoast/redirect-manager had **no** custom redirects on the source.
- **Sitemaps**: `/sitemap.xml` (index) + `/{type}-sitemap.xml` mirror the
  original Yoast layout, generated from the `pages` table.

## Rebuild from scratch
Requires the live WordPress source (`hazembefore.test`) to be running.
Source host/DB are set in `.env` (`WP_SOURCE_HOST`, `WP_DB_DATABASE`).
```bash
php artisan migrate:fresh          # create tables
php artisan db:seed                # roles + admin users
php artisan wp:capture --fresh     # capture pages/cases/faq/archives from WP
php artisan wp:import-articles --prune  # import articles into the posts table
php artisan wp:redirects           # build WordPress canonical 301s
```
`wp:import-articles` reads the live WP DB via the `wp` connection (configured
from `WP_DB_*` in `.env`); `--prune` removes the captured `wp_type=post` pages
so articles render dynamically. Re-running is idempotent (keyed by the WP id).
Then copy the theme assets into `public/` (static files only) — see the asset
copy described above. Source of truth for URLs: `database/seeders/raw/urls.json`
(generated from the live site via `get_permalink`) and `sitemap_urls.txt`.

## Source site facts (for reference)
- DB `hazembefore`, theme `hello-elementor`, Elementor 4.3.6 + Pro 4.1.1
- Polylang: `en` (default, no prefix) + `ar` (`/ar/` prefix); permalinks
  `/%postname%/` with hierarchical pages
- Content: 63 pages (31 en + 32 ar), 126 posts, 4 cases, 68 medical_faq,
  custom types `cases` (slug `cases`) and `medical_faq` (slug `medical-faq`,
  taxonomy `faq-topic`)
- Two source fixes were needed before capture: (1) the site's `.htaccess` was
  empty (missing the WordPress rewrite block) so every permalink 404'd — restored
  the standard block; (2) the rendered HTML contains invalid UTF-8 byte sequences
  (the site runs a `hazem-encoding-fix` plugin), so `wp:capture` scrubs each
  captured string to valid UTF-8 (`mb_scrub`) before storing.

## Online appointments
A calendar-based booking system on `/online-appointment/` (served at its exact
original URL via `PageController` → `AppointmentController`, so it stays 200):
- **Clinics by location:** the `appointment_types` table holds the doctor's
  **clinics** — Egypt (EGP) and UAE (AED) — each with its own `price`, `currency`
  and an `is_active` flag. Only **active** clinics accept bookings, so the doctor
  toggles which location he is currently at from the dashboard (Egypt on → UAE
  off), and that controls which currency/prices the site shows. Prices are edited
  from the dashboard. (`AppointmentType` is kept as the model name; it represents
  a clinic.)
- **Slots & bookings:** `appointment_slots` (clinic + `starts_at` + status
  available/booked/closed), `appointment_bookings` (patient name/phone, status,
  amount, currency, payment ref). `AppointmentSlot::scopeBookable()` = available +
  future + clinic active.
- **Dashboard:** Filament **Appointments** group — **Clinics** (price, currency,
  slot count, inline Active toggle), **Time Slots** (clinic badge, date/time,
  status badge, quick Close/Re-open + filters), **Bookings** — gated by the
  `manage_appointments` permission.
- **Booking page:** a month **calendar** (vanilla JS, built from the active
  clinics' bookable slots) → pick a day → pick a time → name + **phone (required)**.
- **Payment is optional** (`App\Support\Appointments::book()`): an unpaid booking
  is saved `unconfirmed` and the slot **stays open** (others can still book it);
  the doctor is notified on Telegram that an unconfirmed booking needs follow-up.
  `confirmPaid()` (called by the Stripe flow, wired later) marks it `paid` and
  closes the slot. Stripe keys: `STRIPE_*` in `.env`.
- **Telegram** (`App\Support\Telegram` + `telegram:poll`, scheduled every minute):
  notifies on each booking, and lets the doctor close/open slots for walk-ins from
  the bot (`/slots`, `/closed`, inline buttons). Inert until `TELEGRAM_BOT_TOKEN`
  + `TELEGRAM_CHAT_ID` are set (@BotFather). Long-poll — no public webhook needed.

## Notes / for production
- Canonical/OG URLs in captured heads point to `hazemlaravel.test` (the source
  host was rewritten on capture). For the real production domain, re-run capture
  with `--source`/host rewrite pointed at the production host, or add a host
  rewrite step, so canonicals match the Search Console property.
- Re-running `wp:capture` is idempotent (keyed by `path`).
- Fixing the source first: the live WP had a fatal (Elementor Jetpack autoloader
  manifest missing). Regenerated `vendor/composer/jetpack_autoload_{psr4,classmap,
  filemap}.php` for `plugins/elementor` from its composer autoload files.
