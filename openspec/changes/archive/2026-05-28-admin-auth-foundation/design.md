## Context

The IRM project is a school CMS built in pure PHP 8.0+ with PDO/MySQL, no Composer, no build tooling. The existing `/admin` directory contains placeholder CRUD pages with a single-tier `admin_users` table and no role hierarchy. All existing admin files and the `admin_users` table are being replaced in this change. The public side (`index.php`, `page.php`, `includes/`, `config/`, `assets/`) is untouched.

The two-tier config pattern must be preserved: `config.json` for school identity/branding (read via `cfg()`), MySQL for runtime data. DB credentials come from `.env` via the existing `env()` helper. The `db()` PDO singleton in `includes/db.php` remains the shared database connection for all query files.

See [C4 Container Diagram](admin-auth-c4.drawio) for the system architecture. See ADRs [0007](../../../docs/adr/0007-replace-admin-users-with-auth-users.md)–[0011](../../../docs/adr/0011-theme-preference-in-database.md) for key decisions.

## Goals / Non-Goals

**Goals:**
- Replace all `/admin` pages with a working auth foundation (login, logout, dashboard, profile, OIDC config)
- Implement role-based access control with four roles: `sa`, `admin`, `faculty`, `user`
- First-launch setup screen that creates the `sa` account (`username='admin'`, bcrypt password)
- `auth_config` singleton UI (sa-only) for OIDC/SAML provider configuration
- Bootstrap 5.3 CDN admin shell with dark/light/system theme stored per user in DB
- Single `sql/schema.sql` ground truth replacing all previous SQL files

**Non-Goals:**
- OIDC/SAML authentication flow (login redirect, callback, token exchange) — config stored only
- Content management pages (menus, news, slides, links, blocks) — future steps
- User management UI (list/create/deactivate faculty and user accounts) — future step
- Public-side changes of any kind

## Decisions

### D1: Database schema scope

All previous `sql/` migration files are deleted. `sql/schema.sql` defines only the two new tables — `auth_users` and `auth_config`. Existing content tables (`menus`, `pages`, `content_blocks`, `hero_slides`, `popular_links`, `news`) are already in the database and are not touched by this change; they will be managed by future changes when content management pages are built.

**Why**: The original plan included all tables in schema.sql, but the user explicitly narrowed scope during implementation. Including unrelated DDL in an auth-focused migration adds unnecessary risk (DROP IF EXISTS on content tables during an auth deploy).

### D1a: Database connection architecture

DB credentials are read exclusively from environment variables — never from `config.json` or any committed file. The chain is:

```
.env (or real env vars)
  → load_dotenv() / env() in config.php          ← credential reading
  → db_dsn(), db_user(), db_pass() in config.php ← DSN construction
  → db() singleton in includes/db.php             ← PDO created once per request
  → includes/db_login.php                         ← domain query functions
  → includes/db_profile.php
  → includes/db_auth_config.php
```

`config.php` is the **only** file that reads environment variables. `includes/db.php` consumes those helpers to build the PDO singleton. All `db_*.php` domain files call `db()` — they never reference env vars directly.

`config.php` also calls `session_start()` (with a `session_status()` guard) so sessions are available to any file that includes it. `includes/auth.php` carries its own guard for pages that may not pull in `config.php` early.

`config.php` also exposes `APP_SECRET` as a PHP constant (read from `APP_SECRET` env var), available for CSRF or session hardening in future changes.

The `.env` uses discrete vars only (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`). The `DATABASE_DSN`, `DATABASE_USER`, `DATABASE_PASSWORD` alternative names are not supported — `db_dsn()`, `db_user()`, `db_pass()` read the discrete vars directly.

### D2: auth_users table design

```sql
CREATE TABLE auth_users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NULL UNIQUE,
  email      VARCHAR(255) NULL UNIQUE,
  name       VARCHAR(255) NOT NULL,
  role       ENUM('sa','admin','faculty','user') NOT NULL DEFAULT 'user',
  password   VARCHAR(255) NULL,
  is_active  TINYINT(1)   NOT NULL DEFAULT 1,
  theme      ENUM('light','dark','system') NOT NULL DEFAULT 'system',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

- `username` — only populated for `sa` (`'admin'`); NULL for all other users
- `email` — NULL for `sa`; required unique for all other users (OIDC identifier)
- `password` — bcrypt hash, only for `sa`; NULL for OIDC users
- MySQL allows multiple NULLs in UNIQUE columns — no workaround needed
- See [ADR-0007](../../../docs/adr/0007-replace-admin-users-with-auth-users.md), [ADR-0009](../../../docs/adr/0009-sa-username-password-not-oidc.md), [ADR-0011](../../../docs/adr/0011-theme-preference-in-database.md)

### D3: auth_config table design (singleton)

```sql
CREATE TABLE auth_config (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider      ENUM('google','okta') NOT NULL,
  label         VARCHAR(255) NOT NULL,
  logo_url      VARCHAR(500) NULL,
  type          ENUM('OIDC','SAML') NOT NULL,
  issuer_url    VARCHAR(500) NOT NULL,
  client_id     VARCHAR(500) NOT NULL,
  client_secret VARCHAR(500) NOT NULL,
  pkce_enabled  TINYINT(1)   NOT NULL DEFAULT 0,
  scopes        VARCHAR(500) NOT NULL DEFAULT 'openid email profile',
  redirect_uri  VARCHAR(500) NULL,
  is_active     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

- Treated as singleton (max 1 row). `auth_config_save()` DELETEs all rows then INSERTs fresh.
- `client_secret` always rendered as `<input type="password">` in the admin UI, never as plain text.
- `is_active = 0` by default — admin must explicitly enable after configuring.
- See [ADR-0008](../../../docs/adr/0008-auth-config-singleton-one-provider.md)

### D4: Session payload and auth guard

On successful login, `$_SESSION['auth']` is populated:

```php
$_SESSION['auth'] = [
    'id'    => $user['id'],
    'name'  => $user['name'],
    'role'  => $user['role'],   // 'sa'|'admin'|'faculty'|'user'
    'theme' => $user['theme'],  // 'light'|'dark'|'system'
];
```

`includes/auth.php` provides two functions:

```php
function require_auth(string ...$roles): void
// Zero args = any logged-in user. With args = must match one of the given roles.
// Redirects to /admin/login.php if unauthenticated.
// Renders admin/403.php if authenticated but wrong role.

function current_user(): ?array
// Returns $_SESSION['auth'] or null if not logged in.
```

Usage on every protected page:
```php
require_once __DIR__ . '/../includes/auth.php';
require_auth();              // any authenticated user
require_auth('sa');          // auth_config.php
require_auth('sa','admin');  // future content pages
```

### D5: First-launch detection and setup flow

`admin/login.php` queries `SELECT COUNT(*) FROM auth_users` at the top. If zero rows, it renders the setup form instead of the login form. The setup form collects only a password (username `'admin'` and name `'Administrator'` are hardcoded; role is `'sa'`).

Password validation (server-side): minimum 8 characters, at least 1 uppercase letter, 1 digit, 1 special character. The regex is defined once as `define('PWD_REGEX', '/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/')` in `includes/auth.php` — a global constant shared by all pages that validate passwords. `admin/login.php` includes `auth.php` explicitly to access it.

On successful setup, the `sa` row is inserted and the user is redirected to login. No auto-login after setup — the explicit login step confirms the password was set correctly.

### D6: Login form states

`admin/login.php` renders dynamically based on DB state:

| Condition | Form shown |
|---|---|
| Zero rows in `auth_users` | Setup screen (set admin password) |
| Rows exist, no active OIDC provider | Username + password form only |
| Rows exist, active OIDC provider | Username + password form + OIDC button below |

The OIDC button displays `auth_config.label` (e.g., "Login with Google"). Clicking it initiates the OIDC flow (implemented in a future change).

### D7: Admin shell and theming

`admin/_layout.php` is included at the top of every admin page. It:
1. Reads `$_SESSION['auth']['theme']` → sets `data-bs-theme` on `<html>`
2. Loads Bootstrap 5.3 from CDN (`https://cdn.jsdelivr.net/npm/bootstrap@5.3`)
3. Loads `admin/style.css` for custom overrides
4. Renders the top navbar (app name, user name + role badge, theme switcher, logout)
5. Renders the left sidebar with role-filtered nav links
6. Opens the main content `<div>`

`admin/_layout_end.php` closes the main content div and `<body>`/`<html>`. Flash messages (`$_SESSION['flash']`) are rendered in the layout before the content area and cleared immediately.

Theme change: a small POST to `admin/profile.php?action=theme` updates `auth_users.theme` and `$_SESSION['auth']['theme']`, then redirects back. No JS fetch required — a simple form post with `<select>` in the navbar works without JS.

CSRF token: `_layout.php` generates `$_SESSION['csrf']` automatically (if not already set) at the top of every layout-wrapped page. Individual protected pages do not generate the token themselves. `admin/login.php` is the exception — it does not use the layout and generates its own token. The token is rotated (new value written) after successful setup and successful login to prevent fixation.

Bootstrap 5.3 native dark mode (`data-bs-theme="dark"`) handles all component theming automatically. `system` theme: a `<script>` block in `<head>` (before Bootstrap loads) reads `prefers-color-scheme` and sets the attribute synchronously, preventing FOUC.

### D8: File layout

```
sql/
  schema.sql                ← auth_users + auth_config only (content tables not included)

includes/
  auth.php                  ← require_auth(), current_user(), PWD_REGEX constant
  db_login.php              ← auth_user_count(), auth_user_find_by_username(),
                               auth_user_create_sa(), auth_config_active()
  db_profile.php            ← auth_user_find_by_id(), auth_user_update_password(),
                               auth_user_update_theme()
  db_auth_config.php        ← auth_config_get(), auth_config_save(),
                               auth_config_clear(), auth_config_toggle()

admin/
  _layout.php               ← admin chrome (navbar, sidebar, theme, flash, CSRF generation)
  _layout_end.php           ← closing tags
  style.css                 ← Bootstrap overrides, custom admin CSS
  login.php                 ← requires auth.php; first-launch setup + login form + OIDC button;
                               generates own CSRF (standalone, no layout)
  logout.php                ← clears session cookie, session_destroy(), redirect to login
  index.php                 ← minimal dashboard (welcome message)
  profile.php               ← change password + theme switcher
  auth_config.php           ← OIDC/SAML provider settings (sa only)
  403.php                   ← access denied page
```

See [ADR-0010](../../../docs/adr/0010-per-page-db-function-files.md) for the per-page DB file rationale.

## Risks / Trade-offs

- **Nullable dual-key on auth_users** → `username` NULL for non-sa, `email` NULL for sa. MySQL handles this correctly with UNIQUE (NULLs are not considered duplicates). Any future query that looks up a user must check both columns or use the correct one for the role. Mitigation: DB functions encapsulate this logic; pages never query `auth_users` directly.

- **client_secret in plaintext in DB** → Same risk level as any CMS storing API keys. Acceptable for this deployment model. Mitigation: DB access is restricted; secret is never logged or output as plain text; encryption at rest can be added later.

- **Bootstrap CDN dependency** → Admin UI is broken if CDN is unreachable (e.g., air-gapped deployment). Mitigation: Document in README that Bootstrap can be self-hosted by downloading and placing in `admin/vendor/bootstrap/`; update `_layout.php` to point to local copy.

- **First-launch re-trigger** → If `auth_users` is accidentally emptied in production, the setup screen reappears. Mitigation: The setup creates only the `sa` row; no existing data is overwritten. The risk is the sa password being reset, not data loss.

## Migration Plan

1. Delete all files under `admin/` (existing pages, layout, login, logout, style)
2. Delete all files under `sql/` (existing migration files)
3. Run `sql/schema.sql` against the database (drops `admin_users`, creates all tables)
4. Deploy new `admin/` files and `includes/auth.php`, `includes/db_*.php`
5. Visit `/admin/` — first-launch setup screen appears; set the sa password
6. Log in and verify dashboard loads

Rollback: restore from a DB backup taken before step 3. The public site is unaffected throughout.

## Open Questions

_(none — all design decisions resolved during the grill-me session)_
