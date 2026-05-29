## Why

The project has no functional admin authentication system — the existing `/admin` pages are placeholder CRUDs with a hardcoded `admin_users` table and no role hierarchy. Building the admin side requires a proper auth foundation: a superuser setup flow, role-based access control, and a configuration layer for future OIDC/SAML provider login — before any content management pages can be built on top.

## What Changes

- **BREAKING**: Drop `admin_users` table; replace with `auth_users` (new schema, new roles)
- **BREAKING**: Delete all existing `/admin/*.php` pages; rebuild from scratch
- **BREAKING**: Delete all existing `sql/` migration files; replace with single `sql/schema.sql` ground truth
- Add `auth_config` table for OIDC/SAML provider singleton configuration
- Add `includes/auth.php` — session guard (`require_auth(...$roles)`) and `current_user()` helper
- Add per-page DB function files: `includes/db_login.php`, `includes/db_profile.php`, `includes/db_auth_config.php`
- Add admin shell: `admin/_layout.php`, `admin/_layout_end.php`, `admin/style.css` (Bootstrap 5.3 CDN, plain CSS, 3 themes)
- Add `admin/login.php` — first-launch setup screen + login form + OIDC provider buttons
- Add `admin/logout.php`, `admin/index.php` (minimal dashboard), `admin/profile.php`, `admin/auth_config.php`, `admin/403.php`

## Capabilities

### New Capabilities

- `admin-auth`: Authentication system — `auth_users` table (roles: sa, admin, faculty, user), first-launch setup screen, username/password login for sa, session guard with role enforcement, password reset on profile page, theme preference per user
- `oidc-config`: OIDC/SAML provider configuration — `auth_config` singleton table (google/okta), sa-only admin UI to configure/toggle/clear provider, client_secret always rendered as password field, provider buttons shown on login page when active
- `admin-shell`: Admin chrome — Bootstrap 5.3 CDN layout, sidebar navigation (role-aware), dark/light/system theme switching stored in DB, flash messages, CSRF protection on all POST forms

### Modified Capabilities

_(none — this is a clean rebuild with no existing specs to delta)_

## Impact

- **Removed**: `admin_users` table, all previous `/admin/*.php` files, all previous `sql/` migration files
- **Added**: `auth_users`, `auth_config` tables; `sql/schema.sql` (new ground truth including all existing tables: menus, pages, content_blocks, hero_slides, popular_links, news)
- **Unchanged**: all public-side files (`index.php`, `page.php`, `includes/db.php`, `includes/functions.php`, `config.php`, `config/`, `assets/`)
- **Dependencies**: PHP 8.0+ (sessions, bcrypt via `password_hash()`), PDO/MySQL, Bootstrap 5.3 (CDN, no install), existing `env()` and `db()` helpers from `config.php` and `includes/db.php`
