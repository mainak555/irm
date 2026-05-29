# Project: Open School CMS

## Objective

A **generic, open-source School CMS** that any school or training institute can
self-host by editing one JSON file and running a SQL import — no code changes
required.

**Core principle — two-tier configuration:**

| Tier | Source | Who edits it | What it holds |
|------|--------|--------------|---------------|
| **Identity & branding** | `config.json` | Deployer (file edit) | School name, address, about text, color scheme, fonts, social URLs, footer copy |
| **Live content** | MySQL database | Admin panel (web UI) | Notices/news, results, hero slides, nav menus, page content, popular links |

This means:
- A new school deploys by updating `config.json` and seeding the DB — **no PHP changes, no hardcoded strings anywhere**.
- The database is reserved for content that changes at runtime (notices, results, events, user-uploaded images).
- The admin panel manages only dynamic content; branding/identity is out of its scope.

**Deploy goals:**
- Zero Composer / NPM dependencies — `git clone` + edit `config.json` + `mysql < schema.sql`.
- Runs on shared hosting, VPS, or `php -S localhost:8080`.
- PHP 8.0+, MySQL 5.7+ / MariaDB 10.3+.

---

## Tech Stack

| Layer         | Choice                                                  |
|---------------|---------------------------------------------------------|
| Language      | PHP 8.2+ (`declare(strict_types=1)` everywhere)         |
| Config        | `config.json` — parsed once, cached in a static helper  |
| Database      | MySQL 5.7+ / MariaDB 10.3+, utf8mb4                    |
| DB access     | PDO, `FETCH_ASSOC`, `EMULATE_PREPARES = false`          |
| Auth          | PHP sessions + `password_verify()` (bcrypt)             |
| Frontend      | Vanilla HTML / CSS / ES6 JS — no framework              |
| CSS variables | Color scheme injected from `config.json` at render time |
| Fonts         | Google Fonts — family names come from `config.json`     |
| Environment   | `.env` for DB credentials only; branding is in JSON     |
| Deployment    | Apache / Nginx / `php -S`; no build step                |

---

## config.json — Schema

`config.json` lives at the project root. It is the **single source of truth**
for everything that identifies the school. It is **never written by PHP** —
only by a human deployer.

```jsonc
{
  "school": {
    "title":    "Your School Full Name",
    "subtitle": "Location — Established YYYY",
    "tagline":  "Optional one-line motto"
  },
  "brand": {
    "colors": {
      "cream":      "#f5f0e8",
      "accent":     "#b5451b",
      "terracotta": "#b5451b",
      "olive":      "#5a6e3a",
      "navy":       "#15172a"
    },
    "fonts": {
      "heading": "Crimson Text",
      "body":    "Source Sans 3"
    }
  },
  "contact": {
    "address": [
      "School Full Name",
      "Street / PO",
      "City, State",
      "PIN 000000"
    ],
    "phone": "+91 ...",
    "fax":   "+91 ...",
    "email": "contact@yourschool.org"
  },
  "social": {
    "facebook": "https://facebook.com/yourpage"
  },
  "about_short": "One or two sentences shown in the hero area of the home page.",
  "footer": {
    "copyright":  "Copyright © 2026 — All Rights Reserved",
    "powered_by": "Powered by Open School CMS"
  },
  "quick_links": [
    { "label": "Homepage",            "url": "index.php" },
    { "label": "Contact Us",          "url": "page.php?slug=contact" },
    { "label": "Privacy Policy",      "url": "page.php?slug=privacy" },
    { "label": "Terms of Use",        "url": "page.php?slug=terms" }
  ]
}
```

**Rules for config.json:**
- All string values are HTML-escaped with `h()` before output — treat them as plain text.
- `brand.colors` values are injected as CSS custom properties in `<head>`; they must be valid CSS color values.
- `brand.fonts` values are used in the Google Fonts URL — must be valid Google Fonts family names.
- `quick_links` replaces the former `quick_links` DB table. Static footer links belong here.
- Adding new top-level keys is fine; PHP reads them via `cfg('key.subkey')`.

---

## Directory Layout

```
project-root/
├── config.json             # ← School identity & branding. Edit this to deploy.
├── config.json.example     # Template with all keys documented
├── config.php              # .env loader + db_dsn/user/pass helpers + session_start
├── .env                    # DB credentials only (gitignored)
├── .env.example            # Template
├── index.php               # Public home page
├── page.php                # Generic content page (?slug=X)
├── news.php                # Single news/notice article (?slug=X)  [planned]
├── README.md               # Setup guide
│
├── sql/
│   └── schema.sql          # DROP/CREATE 7 tables + minimal seed (no school data)
│
├── includes/
│   ├── db.php              # db() PDO singleton
│   ├── config.php          # cfg() helper — reads config.json, static-cached
│   ├── functions.php       # h(), public_menu(), hero_slides(), latest_news(), etc.
│   ├── header.php          # <head> (injects CSS vars from config.json) + nav
│   └── footer.php          # footer (reads quick_links + contact from config.json)
│
├── admin/
│   ├── _layout.php         # Admin chrome: topbar + collapsible sidebar (requires auth)
│   ├── _layout_end.php     # Closes admin chrome, loads Bootstrap JS
│   ├── login.php           # Login + first-launch setup form
│   ├── logout.php          # Session destroy + redirect
│   ├── index.php           # Dashboard
│   ├── profile.php         # Change password + theme preference
│   ├── users.php           # User management (sa, admin)
│   ├── auth_config.php     # OIDC provider configuration (sa only)
│   ├── 403.php             # Access denied page
│   ├── style.css           # Material Shadcn theme (light/dark tokens, Inter font)
│   └── auth/
│       ├── redirect.php    # Builds PKCE authorization URL → redirects to provider
│       ├── callback.php    # OAuth callback handler
│       └── error.php       # OIDC provisioning error page
│
└── assets/
    └── css/
        └── site.css        # Public stylesheet (uses CSS vars set by header.php)
```

> **Removed from this layout vs. earlier version:**
> `admin/settings.php` — no longer needed; branding/identity lives in `config.json`.

---

## Database Schema (7 Tables)

The database holds **only content that changes at runtime**.

| Table            | Purpose                                                   |
|------------------|-----------------------------------------------------------|
| `auth_users`     | Admin login accounts (email/sentinel, bcrypt hash, role)  |
| `menus`          | Primary nav items (label, slug, external flag, sort)      |
| `pages`          | Generic content pages (slug, title, body_html)            |
| `content_blocks` | Named HTML chunks in fixed home-page slots                |
| `hero_slides`    | Carousel images (image_path, caption, sort_order)         |
| `popular_links`  | "Most Popular Links" block on home page                   |
| `news`           | News, notices, events (title, slug, body_html, date)      |

> **Removed vs. earlier version:**
> `settings` table — replaced by `config.json`.
> `quick_links` table — moved to `config.json` (`quick_links` array).

Every content table has `is_public TINYINT(1)` for draft/live toggling.

The seed data in `schema.sql` is **school-neutral** (placeholder titles and
lorem-style copy). Real content comes from `config.json` + admin panel.

---

## Architecture Patterns

### Two Config Helpers

```php
// config.json helper — dot-notation access
cfg('school.title')         // "Your School Full Name"
cfg('brand.colors.accent')  // "#b5451b"
cfg('contact.phone')        // "+91 ..."

// DB helper — dynamic content only
setting() is REMOVED.
// All former setting() calls are replaced by cfg() calls.
```

### CSS Variable Injection (header.php)

`header.php` reads `cfg('brand.colors')` and `cfg('brand.fonts')` and emits:

```html
<style>
  :root {
    --cream:      #f5f0e8;
    --accent:     #b5451b;
    --navy:       #15172a;
    ...
    --font-head:  'Crimson Text', serif;
    --font-body:  'Source Sans 3', sans-serif;
  }
</style>
```

`site.css` uses `var(--accent)` etc. — never hardcodes color values.
A deployer changes the color scheme entirely by editing `config.json`, no CSS edit needed.

### Request Flow (public pages)
```
GET /page.php?slug=courses
  → config.php  (env vars, session)
    → includes/db.php  (PDO singleton)
      → includes/config.php  (cfg() — reads config.json, static-cached)
        → includes/functions.php  (h, public_menu, …)
          → includes/header.php  (injects CSS vars, nav)
            → page body
              → includes/footer.php  (contact + quick_links from cfg())
```

### Request Flow (admin POST)
```
POST /admin/news.php
  → require_admin()     # session check, redirect to login if absent
    → csrf_check()      # 400 die on token mismatch
      → PDO prepare/execute  # named placeholders only
        → $_SESSION['flash'] + Location redirect
```

### Content Block Pattern
`content_blocks` rows hold admin-authored HTML. They are echoed **without**
`h()` because they are trusted admin input, not user input. All other
DB/config values go through `h()`.

### URL Scheme
| URL                      | Resolves to                   |
|--------------------------|-------------------------------|
| `/index.php`             | Home page                     |
| `/page.php?slug=courses` | Generic content page          |
| `/news.php?slug=X`       | Single news article (planned) |
| `/admin/`                | Admin dashboard               |
| `/admin/login.php`       | Login                         |

---

## Conventions

### PHP
- `declare(strict_types=1)` on every PHP file.
- `require_once` (not `include`) for shared helpers.
- All DB-sourced and config-sourced strings go through `h()` before output.
  Exception: `body_html` columns — admin-authored HTML, echoed raw.
- All DB writes use PDO prepared statements with **named placeholders** (`:key`).
  No string interpolation in SQL ever.
- Boolean columns are `TINYINT(1)`: cast with `(int)` on read, `isset()` on POST.
- `cfg()` uses a static cache (reads `config.json` once per request).
- `block()` uses a static cache (queries `content_blocks` once per request).

### Audit Columns

Every DB table carries four standard audit columns (see ADR-0015):

| Column | Type | Rule |
|--------|------|------|
| `created_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP | Set once on insert; always UTC |
| `updated_at` | TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Auto-updated on every write; always UTC |
| `created_by` | INT UNSIGNED NULL FK → `auth_users.id` ON DELETE SET NULL | Populated via `audit_by()` at insert; `NULL` = system/bootstrap |
| `updated_by` | INT UNSIGNED NULL FK → `auth_users.id` ON DELETE SET NULL | Populated via `audit_by()` at every write |

UTC is enforced at two layers: `SET time_zone = '+00:00'` in `schema.sql` AND in every PDO connection (`includes/db.php`).

**UI display standard:**
- Only `updated_at` / `updated_by` is surfaced in the UI. `created_at` / `created_by` are stored but never shown.
- Every timestamp element carries `data-utc-ts="[raw UTC string]"`. The global JS utility in `admin/_layout.php` converts it to the browser's local timezone on `DOMContentLoaded`. No inline timezone label — the tooltip contains `"[local time] ([IANA tz])  ·  [raw UTC] UTC"`.
- Audit helper: `includes/audit.php` provides `audit_by(): ?int` — the single call site for all `created_by` / `updated_by` writes.

### CSS / Theming
- **All** color and font values come from CSS custom properties set in `<head>`.
- `site.css` references `var(--accent)`, `var(--navy)`, `var(--font-head)` etc. — never literal color values.
- Admin uses `admin/style.css` only — no cross-contamination with `site.css`.
- BEM-light class naming: `.band-cream`, `.four-col`, `.news-list`, `.carousel-stage`.
- Responsive breakpoints in `site.css` only; no inline `@media` queries.

### Security
- CSRF token on every admin POST form: `<input name="_csrf" value="<?= h(csrf_token()) ?>">`.
- `csrf_check()` is the first call in every POST handler.
- `require_admin()` at the top of every admin page, before any output.
- `session_regenerate_id(true)` on successful login.
- `.env` is gitignored — DB credentials never in source.
- `config.json` contains no secrets (no DB passwords, no API keys).
- `APP_DEBUG=false` in production.

### Admin Forms
- Each editable table row has an inline `<form method="post">` wrapping the `<tr>`.
- Each delete action is a separate `<form>` with `onsubmit="return confirm(…)"`.
- Flash messages: `$_SESSION['flash'] = ['type' => 'ok'|'err', 'msg' => '…']`,
  rendered by `_layout.php`.

### Naming
- PHP functions: `snake_case`
- DB columns: `snake_case`
- JSON keys: `camelCase` for nested objects within a group, `snake_case` at top level
- CSS classes: `kebab-case`
- PHP/HTML files: `kebab-case`
- DB tables: plural `snake_case`

---

## Environment Variables (.env)

Only DB credentials and debug flag. **No school content here.**

| Variable            | Purpose                               | Default      |
|---------------------|---------------------------------------|--------------|
| `DATABASE_DSN`      | Full PDO DSN (overrides discrete vars)| —            |
| `DATABASE_USER`     | DB username                           | —            |
| `DATABASE_PASSWORD` | DB password                           | —            |
| `DB_HOST`           | DB host                               | `127.0.0.1`  |
| `DB_PORT`           | DB port                               | `3306`       |
| `DB_NAME`           | DB name                               | `school_cms` |
| `DB_USER`           | DB username (alt)                     | —            |
| `DB_PASS`           | DB password (alt)                     | —            |
| `APP_DEBUG`         | `true` shows errors in browser        | `false`      |

---

## Maturity Roadmap

Features are added incrementally. The config.json schema grows as new
configurable areas are identified.

### Done (admin foundation)
- [x] Admin authentication: login, logout, CSRF, session guard, role enforcement
- [x] First-launch setup screen (creates SA account)
- [x] Per-user theme preference (Light / Dark / System), stored in DB
- [x] OIDC / PKCE SSO provider configuration (sa only)
- [x] OIDC authorization redirect + callback handler
- [x] User management page: list, add, edit, delete, role/SSO/active toggles
- [x] Role hierarchy + creator-scoped same-rank peer protection
- [x] Audit columns (`created_by`, `updated_by`, timestamps) on all tables
- [x] Collapsible sidebar with floating hover-reveal mode

### Current (v0.1 — public front-end)
- [x] Public home page with carousel, welcome block, news list, popular links
- [x] Generic `page.php` for content pages
- [x] Admin CRUD: menus, blocks, links, news, slides
- [x] `config.json` / `cfg()` approach implemented

### Near-term (v0.2)
- [ ] `news.php` — public single-article view
- [ ] `admin/pages.php` — CRUD for content pages (currently SQL-only)
- [ ] Image upload in `admin/slides.php` (currently path/URL string)

### Future (v0.3+)
- [ ] WYSIWYG editor (TinyMCE) for `content_blocks` and `news` textareas
- [ ] Results module (DB table + admin upload + public view)
- [ ] Gallery module
- [ ] `config.json` schema validator / linter (CLI or admin page)
