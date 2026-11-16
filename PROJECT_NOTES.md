# Prof. Dr. Hazem El Fezary — Laravel replica of `hazemfezary` (WordPress)

A Laravel 13 + Filament application that reproduces the bilingual (English +
Arabic/Polylang) WordPress site `hazemfezary` (Elementor Pro, theme
**hello-elementor**) with a MySQL backend, an admin dashboard, a Yoast-style SEO
module, Google integration and a redirect manager.

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
- **Captured pages** — the fully-rendered HTML of all 125 public URLs (63 pages,
  50 posts, 4 cases, 4 EN medical_faq, archives) was captured from the live
  WordPress render and stored in the `pages` table (`path`, `language`,
  `head_html`, `content_html`, `body_class`, `html_lang`, `html_dir`, `wp_*`).
  Served verbatim through `resources/views/captured.blade.php` → pixel-faithful,
  per-language (RTL Arabic intact).
- **Admin sections by content type**: although every URL is stored in one
  `pages` table (so the front-end serves them 1:1 by path), the Filament admin
  splits them into separate sections — **Pages** (63), **Articles** (the 50 blog
  posts), **Medical FAQ** (4), **Cases** (4) — via thin models (`Article`,
  `Faq`, `CaseStudy`) that share the `pages` table with a `wp_type` global
  scope, plus **SEO**, **Google & Marketing**, **Redirects**, **Site Settings**
  and **Users**. The unused chem resources (Posts/Services/Portfolios/Team) are
  hidden from navigation.
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
Requires the live WordPress source (`hazemfezary.test`) to be running.
```bash
php artisan migrate:fresh          # create tables
php artisan db:seed                # roles + admin users
php artisan wp:capture --fresh     # capture every URL from the live WP site
php artisan wp:redirects           # build WordPress canonical 301s
```
Then copy the theme assets into `public/` (static files only) — see the asset
copy described above. Source of truth for URLs: `database/seeders/raw/urls.json`
(generated from the live site via `get_permalink`) and `sitemap_urls.txt`.

## Source site facts (for reference)
- DB `hazemfezary`, theme `hello-elementor`, Elementor 4.3.6 + Pro 4.1.1
- Polylang: `en` (default, no prefix) + `ar` (`/ar/` prefix); permalinks
  `/%postname%/` with hierarchical pages
- Content: 63 pages (31 en + 32 ar), 50 posts, 4 cases, 8 medical_faq,
  custom types `cases` (slug `cases`) and `medical_faq` (slug `medical-faq`,
  taxonomy `faq-topic`)

## Notes / for production
- Canonical/OG URLs in captured heads point to `hazemlaravel.test` (the source
  host was rewritten on capture). For the real production domain, re-run capture
  with `--source`/host rewrite pointed at the production host, or add a host
  rewrite step, so canonicals match the Search Console property.
- Re-running `wp:capture` is idempotent (keyed by `path`).
- Fixing the source first: the live WP had a fatal (Elementor Jetpack autoloader
  manifest missing). Regenerated `vendor/composer/jetpack_autoload_{psr4,classmap,
  filemap}.php` for `plugins/elementor` from its composer autoload files.
