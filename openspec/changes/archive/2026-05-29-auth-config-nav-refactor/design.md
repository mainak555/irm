## Context

The admin section currently has a flat sidebar, an auth config form with a hard-coded provider ENUM dropdown, and a login page that shows the SSO button only when a provider is active (hiding the homegrown form implicitly when the provider takes over). The `auth_config` table carries a `provider ENUM('google','okta')` column with no UI path to update it after this refactor, and a `logo_url` column that was stubbed for the login button icon but never wired up.

The system boundary covers: login page, OIDC redirect handler (new), OIDC callback placeholder (new), protected admin pages, and the MySQL database. Architecture is visualised in `auth-system-c4.drawio`.

Key decisions made during design are captured in `adrs/` (see `adrs/README.md`).

## Goals / Non-Goals

**Goals:**
- Remove `provider` dropdown from the auth config form; wire up `icon_url` (renamed from `logo_url`) for the login button icon
- Login page always shows the homegrown form; adds the provider SSO button alongside it when `is_active = 1`
- Implement `admin/auth/redirect.php` — a real OIDC authorization URL builder with `state`, `nonce`, and PKCE S256 (always on)
- Add `admin/auth/callback.php` placeholder
- Replace flat sidebar with a Bootstrap 5 accordion; add Authorization section (Users + Settings) with role-gated visibility
- Add `admin/users.php` placeholder
- All page guards use only `require_auth()` — no inline session logic

**Non-Goals:**
- OIDC/SAML token exchange and session creation from the callback (deferred to a future cycle)
- SAML-specific redirect logic (same placeholder as OIDC for now)
- Multi-provider support (singleton `auth_config` row unchanged)
- User management implementation (users page is a "coming soon" stub)

## Decisions

### 1. Drop `auth_config.provider` column (see ADR-0001)

The `provider ENUM('google','okta')` column is dropped from the schema. Provider identity is implied by `type` + `issuer_url`. `schema.sql` is updated; existing installs need a manual `ALTER TABLE auth_config DROP COLUMN provider`.

Save validation in `auth_config.php` previously required `provider` to be non-empty. That check is removed. Required fields become: `type`, `issuer_url`, `client_id` (and `client_secret` on first save).

### 2. Rename `logo_url` → `icon_url`; wire up the login button (see proposal)

`auth_config.logo_url` is renamed to `icon_url`. The login page renders the button as:

```html
<button ...>
  <?php if ($config['icon_url']): ?>
    <img src="<?= h($config['icon_url']) ?>" alt="" width="20" height="20">
  <?php endif; ?>
  <?= h($config['label']) ?>
</button>
```

When `icon_url` is NULL, the button renders label-only with no gap or placeholder.

### 3. Both login forms shown simultaneously (see ADR-0002)

`admin/login.php` always renders the homegrown username/password form. When `auth_config_active()` returns a row, the provider SSO button is rendered **in addition**, below a visual separator. There is no hidden escape-hatch URL.

### 4. OIDC redirect handler (see ADR-0004)

`admin/auth/redirect.php`:
1. Calls `auth_config_get()` — if no active config, redirects to login with a flash error
2. Generates `state` (16 random bytes, hex) and `nonce` (16 random bytes, hex)
3. Always generates PKCE: `code_verifier` (32 random bytes, base64url-encoded, no padding) and `code_challenge` (`S256` method: `base64url(hash('sha256', $verifier, true))`). There is no per-config toggle; PKCE S256 is unconditional.
4. Stores `$_SESSION['oidc'] = ['state', 'nonce', 'code_verifier']`
5. Builds the authorization URL by appending `/authorize` to `issuer_url`
6. Sends `header('Location: '.$auth_url)` and exits

`admin/auth/callback.php`:
- Calls `require_auth()` — but the user is not yet authenticated! This page is public (no guard). It renders an informational "SSO callback not yet implemented" message with a link back to login.

### 5. Bootstrap 5 accordion sidebar (see ADR-0003 for role logic)

`admin/_layout.php` sidebar changes from a flat `<nav>` to a Bootstrap 5 accordion. Structure:

```
Dashboard            (all roles, plain link)
Profile              (all roles, plain link)
▼ Authorization      (accordion, visible to sa + admin only)
    Users            → admin/users.php  [require_auth('sa', 'admin')]
    Settings         → admin/auth_config.php  [require_auth('sa')]
```

The accordion item for Authorization gets the Bootstrap `show` class when `$page` is `users` or `auth_config` (auto-expand on active page). The `$page` variable is already derived from `basename($_SERVER['PHP_SELF'], '.php')` in `_layout.php`.

Role visibility is checked via `$_SESSION['auth']['role']` directly in `_layout.php` — this is layout rendering logic, not business logic, so it does not violate the "no custom logic in pages" rule.

### 6. `admin/users.php` placeholder

```php
<?php declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_auth('sa', 'admin');
$page = 'users';
require __DIR__ . '/_layout.php';
?>
<h2>Users</h2>
<p class="text-muted">User management is coming soon.</p>
<?php require __DIR__ . '/_layout_end.php'; ?>
```

## Risks / Trade-offs

- **Breaking DB change** → Mitigation: `schema.sql` is the single source of truth; manual `ALTER TABLE` is the documented migration path for this project (no runner exists).
- **Incomplete SSO flow** → Mitigation: The placeholder callback page clearly states the state; the homegrown login is always available so the system remains fully functional.
- **PKCE code challenge built without a library** → Mitigation: The `S256` calculation is a one-liner (`base64url(hash('sha256', $verifier, raw_output: true))`); no custom crypto, just stdlib functions.
- **Discovery document not fetched** → Mitigation: For this cycle `redirect.php` constructs the authorization URL by appending `/authorize` to `issuer_url`. This works for well-known providers (Okta, Azure AD) that expose a standard path; the discovery fetch is deferred to the full callback cycle.

## Migration Plan

1. Run `ALTER TABLE auth_config DROP COLUMN provider, CHANGE COLUMN logo_url icon_url VARCHAR(500) NULL;` on existing installs, OR drop and recreate the table from the updated `schema.sql` (data loss acceptable — config must be re-entered after migration).
2. Deploy PHP file changes.
3. Re-enter the auth config in the admin UI (provider field gone; icon URL field appears).
4. Verify the login page shows both forms when config is active.
5. Verify the accordion sidebar shows Authorization only for `sa`/`admin` roles.
