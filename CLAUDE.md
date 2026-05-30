# IRM — Claude Code Project Guide

Quick-reference for AI assistants working on this codebase.

---

## What this project is

**IRM** is a self-hosted PHP + MySQL school/institute CMS with an admin panel.
Zero Composer, zero npm, zero build step.

- Public front-end: `index.php`, `page.php`
- Admin panel: `admin/` (Bootstrap 5.3 + Material Shadcn theme)
- All configuration: `.env` (DB + secrets) + `config/config.json` (branding)

---

## Stack

| Layer | Choice |
|---|---|
| Language | PHP 8.2, `declare(strict_types=1)` on every file |
| Database | MySQL 8 / MariaDB — PDO, `FETCH_ASSOC`, named placeholders |
| Admin UI | Bootstrap 5.3 + custom `assets/css/admin.css` (Material Shadcn tokens) |
| Auth | PHP sessions + bcrypt (`password_verify`) + OIDC/SAML via `auth_config` |
| Fonts | Inter (admin), Google Fonts (public, from config.json) |

---

## Conventions

### PHP
- `declare(strict_types=1)` on every file — no exceptions.
- `require_once` for shared helpers, never `include`.
- All DB writes: PDO prepared statements with **named placeholders** (`:key`).
  No string interpolation in SQL ever.
- All output (DB-sourced, config-sourced, user-supplied): escape with `h()`.
  Exception: `body_html` / admin-authored HTML columns — echoed raw intentionally.
- `cfg('key.subkey')` — reads `config/config.json`, static-cached.
- `env('KEY', 'default')` — reads from real env vars or `.env` file.
- `db()` — PDO singleton from `includes/db.php`.
- Boolean DB columns are `TINYINT(1)`. Cast with `(int)` on read, `isset()` on POST.

### Session & Auth
- `config.php` calls `session_start()` — all pages that need sessions require it.
- `require_auth()` — redirects to `/admin/login.php` if no session.
- `require_auth('sa', 'admin')` — 403 if role not in the list.
- `current_user()` — returns `$_SESSION['auth']` array or `null`.
- CSRF token: `$_SESSION['csrf']`, validated with `hash_equals()` before any POST.
- Password regex: `PWD_REGEX` (≥8 chars, uppercase, digit, special char).

### Admin layout pattern
Every protected admin page follows this structure:

```php
<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';  // if cfg() or db() needed

require_auth();           // or require_auth('sa') for restricted pages

$user = current_user();
require __DIR__ . '/_layout.php';    // opens <html>, fixed navbar, fixed sidebar, <main>
?>

<!-- page content here -->

<?php require __DIR__ . '/_layout_end.php'; ?>  // closes </main>, loads BS JS
```

### Flash messages
Set before redirect:
```php
$_SESSION['flash'] = ['type' => 'ok',  'msg' => 'Saved.'];
$_SESSION['flash'] = ['type' => 'err', 'msg' => 'Something failed.'];
```
Rendered automatically by `_layout.php`.

---

## Admin Theme

`assets/css/admin.css` implements the **Material Shadcn** design system on top of
Bootstrap 5.3. All component colors reference `--irm-*` tokens.

### Layout chrome
The admin shell uses a viewport-locked layout (ADR-0021). Navbar and sidebar are
`position: fixed` — they never scroll. **`.admin-main` is the sole scrolling region.**
Any `position: sticky` elements inside page content must be scoped to `.admin-main`
as the scroll root, not to the viewport.

### CSS tokens

| Token | Light | Dark |
|---|---|---|
| `--irm-bg` | `hsl(0,0%,97%)` | `hsl(240,10%,3.9%)` |
| `--irm-fg` | `hsl(20,14.3%,4.1%)` | `hsl(0,0%,98%)` |
| `--irm-card` | `hsl(0,0%,100%)` | `hsl(240,10%,5.5%)` |
| `--irm-border` | `hsl(214,32%,91%)` | `hsl(240,3.7%,15.9%)` |
| `--irm-muted` | `hsl(60,4.8%,95.9%)` | `hsl(240,3.7%,15.9%)` |
| `--irm-muted-fg` | `hsl(25,5.3%,44.7%)` | `hsl(240,5%,64.9%)` |
| `--irm-primary` | black | white |
| `--irm-primary-fg` | white | black |
| `--irm-radius` | `0.75rem` | `0.5rem` |

### Dark / Light / System mode

- Controlled by `data-bs-theme` on `<html>`.
- `_layout.php`: reads `$user['theme']` from DB. For `system`, an inline
  `<script>` in `<head>` sets the attribute before first paint (no flash).
- `login.php` and `403.php`: always use OS preference via the same inline script.
- `body` has class `grain-texture` — activates the subtle noise overlay.

### Bootstrap primary color override

`btn-primary`, `form-check-input:checked`, and focus rings all use
`--irm-primary` (black in light, white in dark) via Bootstrap's
`--bs-btn-bg` / `--bs-primary-rgb` variables.

---

## Roles

| Role | Badge | Typical use |
|---|---|---|
| `sa` | Super Admin | System setup, auth config, all pages |
| `admin` | Admin | User management |
| `faculty` | Faculty | Limited access |
| `user` | User | Basic access |

First account created at setup is always `sa` with `email = 'admin'` (not a real email — this is the sentinel identifier used to log in).

---

## Auth Config (OIDC/SAML)

Stored in `auth_config` table (single row — replace on save).

- **Only `sa` role** can access `admin/auth_config.php`.
- `auth_config_active()` — returns the row if `is_active = 1`.
- Shown on login page only when active.
- The PKCE authorization redirect is in `admin/auth/redirect.php`.
- The callback handler is in `admin/auth/callback.php`.

Google OIDC Issuer URL: `https://accounts.google.com`

---

## Database tables

| Table | Purpose |
|---|---|
| `auth_users` | Admin accounts (username/email, bcrypt hash, role, theme) |
| `auth_config` | Single OIDC/SAML provider row |

Run `mysql < sql/schema.sql` to reset. **WARNING: drops existing tables.**

---

## Environment variables

| Var | Purpose |
|---|---|
| `DB_HOST/PORT/NAME/USER/PASS` | MySQL connection |
| `APP_SECRET` | Session security seed |
| `APP_DEBUG` | `true` only in dev — shows PHP errors |

---

## Key files quick-reference

| File | What it does |
|---|---|
| `config.php` | `.env` loader, `env()`, `cfg()`, `h()`, `db_dsn()`, `session_start()` |
| `includes/db.php` | `db()` PDO singleton |
| `includes/auth.php` | `require_auth()`, `current_user()`, `PWD_REGEX` |
| `includes/audit.php` | `audit_by()` — returns current user ID for `created_by`/`updated_by` writes |
| `includes/db_login.php` | `auth_user_*` functions |
| `includes/db_profile.php` | `auth_user_update_password/theme` |
| `includes/db_auth_config.php` | `auth_config_get/save/clear/toggle` |
| `admin/_layout.php` | Admin shell: opens HTML, topbar, sidebar, `<main>` |
| `admin/_layout_end.php` | Closes `</main>`, loads Bootstrap JS bundle |
| `assets/css/admin.css` | Material Shadcn theme tokens + all component styles |
| `assets/css/themes/` | Public theme pack CSS files (scanned at runtime) |
| `components/carousel.php` | Public carousel PHP partial |
| `sql/schema.sql` | DROP/CREATE `auth_users` + `auth_config` |
| `config/config.json` | School branding — edit to deploy for a new school |
