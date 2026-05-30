# Project: IRM School CMS

## Objective

A lightweight, self-hosted **PHP + MySQL** CMS for schools and training institutes.
Zero Composer, zero npm, zero build step. Clone, edit one JSON file, import the schema,
and it runs.

**Core principle — two-tier configuration:**

| Tier | Source | Who edits | What it holds |
|------|--------|-----------|---------------|
| **Identity & branding** | `config/config.json` | Deployer (file edit or admin General page) | School name, contact, colors, social links, footer, active theme pack |
| **Auth & access** | MySQL (`auth_users`, `auth_config`) | Admin panel | Admin accounts, OIDC provider |
| **Live content** | JSON files under `config/` | Admin panel or direct file edit | Carousel captions, home page copy, nav items |

---

## Tech Stack

| Layer | Choice |
|---|---|
| Language | PHP 8.2+ (`declare(strict_types=1)` on every file) |
| Database | MySQL 8 / MariaDB — PDO, `FETCH_ASSOC`, named placeholders |
| Admin UI | Bootstrap 5.3 + `assets/css/admin.css` (Material Shadcn tokens) |
| Public UI | Vanilla CSS (`assets/css/site.css`) + theme packs (`assets/css/themes/`) |
| Auth | PHP sessions + bcrypt (`password_verify`) + OIDC/PKCE via `auth_config` |
| Fonts | Inter (admin via Google Fonts), configurable family (public via `config.json`) |
| Deployment | Apache / Nginx / `php -S`; Docker with volume mounts for mutable content |

---

## config/config.json — Schema

Single source of truth for school identity and branding. Never written by PHP except
via `admin/config_general.php` (which writes through `cfg()` helpers).

```jsonc
{
  "general": {
    "title":    "School Full Name",
    "subtitle": "Location — Established YYYY",
    "logoUrl":  "/assets/img/logo.png",
    "address":  "Street\nCity, State\nPIN 000000",
    "phone":    "+91 ...",
    "fax":      "+91 ...",
    "email":    "contact@school.org",
    "social": {
      "facebook":  "https://facebook.com/page",
      "twitter":   "",
      "instagram": "",
      "youtube":   ""
    }
  },
  "colors": {
    "cream":    "#f4f1e8",
    "accent":   "#6e7a44"
    // ... other CSS custom property values
  },
  "public": {
    "theme": "classic"         // stem of a file in assets/css/themes/
  },
  "footer": {
    "copyright":  "Copyright © 2026",
    "powered_by": "Powered by: School CMS",
    "quick_links": [
      { "label": "Home",    "url": "index.php" },
      { "label": "Contact", "url": "page.php?slug=contact" }
    ]
  }
}
```

**Rules:**
- `colors` values are injected as `--<key>` CSS custom properties in `<head>`.
- `public.theme` is the slug of a CSS file in `assets/css/themes/`.
- All values are HTML-escaped with `h()` before output.

---

## Other config/ files

| File | Written by | Read by |
|---|---|---|
| `config/slides.json` | `admin/carousel.php` | `components/carousel.php` |
| `config/home.json` | deployer / future admin | `index.php` |
| `config/menu.json` | deployer / future admin | `includes/header.php` |
| `config/external_links.json` | deployer / future admin | public pages |

---

## Directory Layout

```
irm/
├── config.php               — .env loader, env(), cfg(), h(), db_dsn(), session_start()
├── index.php                — Public home page
├── page.php                 — Generic content page (?slug=…)
│
├── config/                  — Docker volume mount
│   ├── config.json          — School identity & branding
│   ├── slides.json          — Carousel captions (filename → caption string)
│   ├── home.json            — Home page content blocks
│   ├── menu.json            — Primary nav items
│   └── external_links.json  — External link groups
│
├── sql/
│   └── schema.sql           — DROP/CREATE auth_users + auth_config
│
├── includes/
│   ├── db.php               — db() PDO singleton (sets UTC timezone)
│   ├── auth.php             — require_auth(), current_user(), PWD_REGEX
│   ├── audit.php            — audit_by(): ?int for created_by/updated_by writes
│   ├── functions.php        — h(), menu_url(), and public helpers
│   ├── db_login.php         — auth_user_count/find/create/update
│   ├── db_profile.php       — auth_user_update_password/theme
│   ├── db_auth_config.php   — auth_config_get/save/clear/toggle
│   ├── db_users.php         — user management helpers
│   ├── header.php           — <head> (CSS vars, fonts, theme), site header, nav
│   └── footer.php           — footer (quick_links + contact from config.json)
│
├── components/              — PHP include partials
│   └── carousel.php         — Bootstrap carousel (reads assets/img/carousel/)
│
├── admin/
│   ├── _layout.php          — Admin shell: HTML open, topbar, sidebar, <main>
│   ├── _layout_end.php      — Closes </main>, loads Bootstrap JS
│   ├── login.php            — Login + first-launch setup
│   ├── logout.php           — Session destroy
│   ├── index.php            — Dashboard
│   ├── profile.php          — Change password + theme preference
│   ├── users.php            — User management (sa, admin)
│   ├── users_ajax.php       — Inline user edit AJAX handler
│   ├── carousel.php         — Carousel image upload + caption editor
│   ├── config_general.php   — General settings: identity, theme pack (sa only)
│   ├── auth_config.php      — OIDC provider config (sa only)
│   ├── 403.php              — Access denied
│   └── auth/
│       ├── redirect.php     — PKCE authorization URL builder
│       ├── callback.php     — OAuth callback handler
│       └── error.php        — OIDC provisioning error page
│
└── assets/                  — Single static-file root (ADR-0020)
    ├── css/
    │   ├── site.css         — Public site stylesheet
    │   ├── admin.css        — Admin Material Shadcn theme (light/dark + Inter)
    │   └── themes/          — Public theme packs; drop .css here to add one
    │       └── classic.css  — Default theme
    └── img/
        ├── logo.png
        └── carousel/        — Docker volume mount; user-uploaded carousel images
```

---

## Database Schema

Only auth tables. Content lives in JSON config files.

### `auth_users`

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `email` | VARCHAR(255) UNIQUE NULL | `'admin'` sentinel for SA; real email for others |
| `name` | VARCHAR(255) NOT NULL | Display name |
| `role` | ENUM(`sa`,`admin`,`faculty`,`user`) | Default `user` |
| `password` | VARCHAR(255) NULL | bcrypt hash; `NULL` for SSO-only users |
| `is_active` | TINYINT(1) | `1` = active |
| `sso` | TINYINT(1) | `1` = SSO-only account |
| `theme` | ENUM(`light`,`dark`,`system`) | Per-user admin theme preference |
| `created_by` | INT UNSIGNED NULL → `auth_users.id` | `NULL` = bootstrap |
| `updated_by` | INT UNSIGNED NULL → `auth_users.id` | |
| `created_at` | TIMESTAMP | UTC |
| `updated_at` | TIMESTAMP | UTC, auto-updated |

### `auth_config`

Single-row OIDC/SAML provider. Replaced on every save.

| Column | Type | Notes |
|---|---|---|
| `id` | INT UNSIGNED PK | |
| `label` | VARCHAR(255) | Login button text |
| `icon_url` | VARCHAR(500) NULL | Provider icon |
| `type` | ENUM(`OIDC`,`SAML`) | |
| `issuer_url` | VARCHAR(500) | OIDC discovery URL |
| `client_id` | VARCHAR(500) | |
| `client_secret` | VARCHAR(500) | Stored plaintext — protect the DB |
| `scopes` | VARCHAR(500) | Default `openid email profile` |
| `redirect_uri` | VARCHAR(500) NULL | Override; `NULL` = auto-detect |
| `is_active` | TINYINT(1) | `0` = hidden, `1` = shown on login page |
| audit columns | see above | same pattern as auth_users |

---

## PHP Conventions

- `declare(strict_types=1)` on every file.
- `require_once` for shared helpers, never `include`.
- All DB writes: PDO prepared statements with **named placeholders** (`:key`). No SQL interpolation.
- All output: escape with `h()`. Exception: `body_html` / admin-authored columns — echoed raw.
- `cfg('key.subkey')` — reads `config/config.json`, static-cached per request.
- `env('KEY', 'default')` — reads real env vars or `.env`.
- `db()` — PDO singleton from `includes/db.php`.
- Boolean DB columns: `TINYINT(1)`. Cast `(int)` on read, `isset()` on POST.

---

## Session & Auth

- `config.php` calls `session_start()` — all pages requiring sessions must `require_once` it.
- `require_auth()` — redirects to `/admin/login.php` if unauthenticated.
- `require_auth('sa', 'admin')` — 403 if role not in list.
- `current_user()` — returns `$_SESSION['auth']` array or `null`.
- CSRF token: `$_SESSION['csrf']`, validated with `hash_equals()` before any POST.
- Password policy: `PWD_REGEX` (≥8 chars, uppercase, digit, special char).

---

## Admin Layout Pattern

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

require_auth();           // or require_auth('sa') for restricted pages
$user = current_user();
require __DIR__ . '/_layout.php';
?>
<!-- page content -->
<?php require __DIR__ . '/_layout_end.php'; ?>
```

Flash messages (set before redirect):
```php
$_SESSION['flash'] = ['type' => 'ok',  'msg' => 'Saved.'];
$_SESSION['flash'] = ['type' => 'err', 'msg' => 'Something failed.'];
```

---

## Roles

| Role | Rank | Access |
|---|---|---|
| `sa` | 3 | Everything — auth config, general settings, all pages |
| `admin` | 2 | User management, carousel, profile |
| `faculty` | 1 | Dashboard, profile |
| `user` | 0 | Dashboard, profile |

First account created at setup is always `sa` with `email = 'admin'` (sentinel).

---

## Audit Columns

Every DB table has four standard audit columns:

| Column | Populated by |
|---|---|
| `created_at` | MySQL `DEFAULT CURRENT_TIMESTAMP` |
| `updated_at` | MySQL `ON UPDATE CURRENT_TIMESTAMP` |
| `created_by` | `audit_by()` at insert; `NULL` = system |
| `updated_by` | `audit_by()` at every write |

UI rule: only `updated_at` / `updated_by` is displayed. `created_*` are stored but never shown (ADR-0015).

---

## Theme System

### Admin theme (per-user)
Stored in `auth_users.theme`. Values: `light`, `dark`, `system`.
Controlled by `data-bs-theme` on `<html>`. System mode uses an inline `<script>` in `<head>` to avoid flash.

### Public theme pack
CSS file in `assets/css/themes/<slug>.css`. Active slug stored in `config.json → public.theme`.
Theme packs are discovered at request time by `glob()` — no manifest (ADR-0017).

---

## Docker

Mutable content must be volume-mounted to survive container rebuilds (ADR-0019):

```yaml
volumes:
  - ./config:/var/www/html/config
  - ./assets/img/carousel:/var/www/html/assets/img/carousel
  # - ./assets/img/gallery:/var/www/html/assets/img/gallery   # when gallery ships
```

Theme pack CSS files are **baked into the image** (shipped code, not user data).

---

## Environment Variables

| Var | Required | Default | Purpose |
|---|---|---|---|
| `DB_HOST` | Yes | `127.0.0.1` | MySQL host |
| `DB_PORT` | No | `3306` | MySQL port |
| `DB_NAME` | Yes | `irm` | Database name |
| `DB_USER` | Yes | — | DB username |
| `DB_PASS` | Yes | — | DB password |
| `APP_SECRET` | Yes | — | Session security seed |
| `APP_DEBUG` | No | `false` | `true` in dev only — shows PHP errors |

---

## Roadmap

### Done
- [x] Admin auth: login, logout, CSRF, session guard, role enforcement
- [x] First-launch setup (creates SA account)
- [x] Per-user theme preference (Light/Dark/System), stored in DB
- [x] OIDC/PKCE SSO provider configuration (sa only)
- [x] User management: list, add, edit, delete, role/SSO/active toggles
- [x] Role hierarchy + creator-scoped same-rank peer protection
- [x] Audit columns on all tables
- [x] Collapsible sidebar with floating hover-reveal
- [x] Public home page with carousel, welcome block, news list
- [x] Carousel admin: image upload + `slides.json` caption management
- [x] General settings admin: identity, theme pack, social links
- [x] Single `assets/` tree — no split between public/ and assets/ (ADR-0020)

### Near-term
- [ ] `news.php` — public single-article view
- [ ] `admin/pages.php` — CRUD for content pages
- [ ] Gallery module + Docker volume mount for `assets/img/gallery/`

### Future
- [ ] WYSIWYG editor for content blocks
- [ ] Results module
- [ ] `config.json` schema validator
