## Why

The current auth config form exposes a hard-coded provider enum (`google`/`okta`) that limits flexibility and leaks an implementation detail into the UI. The sidebar navigation is a flat list that won't scale as the Authorization section grows, and the login page has no fallback strategy — if auth config is inactive the homegrown form works, but both flows are never offered simultaneously.

## What Changes

- **BREAKING** Drop `auth_config.provider` column (ENUM was `google`/`okta`); `schema.sql` updated.
- Rename `auth_config.logo_url` → `auth_config.icon_url` (repurpose the existing stub column for the login button icon).
- Remove the Provider dropdown from the Auth Config form; keep the Type dropdown (`OIDC` / `SAML`).
- Add Button Icon URL field to Auth Config form; icon renders as `<img>` left of button label on the login page; omitted when `icon_url` is NULL.
- Login page always shows the homegrown username/password form; when auth config `is_active=1`, the provider button is shown **in addition** (not instead of) the homegrown form.
- Add `/admin/auth/redirect.php` — builds and executes the OIDC authorization URL redirect (state, nonce, optional PKCE written to session).
- Add `/admin/auth/callback.php` — placeholder page; receives the provider callback and renders "SSO callback not yet implemented."
- Replace flat sidebar with a Bootstrap 5 accordion; add Authorization section (Users + Settings) visible to `admin` and `sa` roles only; Settings visible to `sa` only.
- Add `/admin/users.php` — placeholder page with "Coming soon" notice; `require_auth('sa', 'admin')`.
- Rename Auth Config page link in sidebar from "Auth Config" to "Settings" (URL `/admin/auth_config.php` unchanged).
- Each page uses only `require_auth()` / `require_auth('sa')` / `require_auth('sa', 'admin')` as its guard — no inline session role checks outside that function.

## Capabilities

### New Capabilities

- `auth-provider-redirect`: Redirect handler that constructs the OIDC/SAML authorization URL (with `state`, `nonce`, optional PKCE) and sends the browser to the provider; paired callback placeholder at `/admin/auth/callback.php`.

### Modified Capabilities

- `oidc-config`: Remove `provider` field from form and DB; rename `logo_url` → `icon_url`; wire up icon rendering in the login button; update save validation (provider no longer required).
- `admin-auth`: Login page shows both homegrown form and provider button when auth config is active; homegrown form shown alone when inactive or unconfigured.
- `admin-shell`: Sidebar replaced with Bootstrap 5 accordion; Authorization section (Users + Settings) gated to `admin`+`sa`; Settings item gated to `sa`; accordion auto-expands when active page is inside the Authorization section.

## Impact

- **DB schema:** `auth_config` table — drop `provider` column, rename `logo_url` → `icon_url`. `schema.sql` updated; manual `ALTER TABLE` required on existing installs.
- **PHP files changed:** `admin/_layout.php`, `admin/auth_config.php`, `admin/login.php`, `includes/db_auth_config.php`, `sql/schema.sql`.
- **PHP files added:** `admin/auth/redirect.php`, `admin/auth/callback.php`, `admin/users.php`.
- **No public-facing pages affected.** All changes are within `/admin/`.
- **No Composer dependencies.** PKCE (`code_verifier`/`code_challenge`) implemented with native `random_bytes()` + `hash('sha256', ...)`.
- **CSRF protection unchanged** — all new POST forms follow the existing `$_SESSION['csrf']` + `hash_equals()` pattern.
