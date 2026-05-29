## 1. Database Schema

- [x] 1.1 In `sql/schema.sql`: remove the `provider ENUM('google','okta') NOT NULL` column from `CREATE TABLE auth_config`
- [x] 1.2 In `sql/schema.sql`: rename `logo_url VARCHAR(500) NULL` → `icon_url VARCHAR(500) NULL` in `CREATE TABLE auth_config`
- [x] 1.3 In `sql/schema.sql`: remove `pkce_enabled TINYINT(1) NOT NULL DEFAULT 0` — PKCE is always applied; no toggle needed

## 2. DB Layer

- [x] 2.1 In `includes/db_auth_config.php` `auth_config_save()`: remove `provider` from the INSERT column list and remove `:provider` from the `execute()` array
- [x] 2.2 In `includes/db_auth_config.php` `auth_config_save()`: rename `logo_url` → `icon_url` in the INSERT column list and `:logo_url` → `:icon_url` in the `execute()` array; pass `$data['icon_url'] ?? null`
- [x] 2.3 In `includes/db_auth_config.php` `auth_config_save()`: remove `pkce_enabled` from the INSERT column list and execute array

## 3. Auth Config Form

- [x] 3.1 In `admin/auth_config.php` POST save handler: remove the `provider` required-field validation check
- [x] 3.2 In `admin/auth_config.php` POST save handler: remove `provider` from the `$data` array passed to `auth_config_save()`
- [x] 3.3 In `admin/auth_config.php` form HTML: remove the Provider `<select name="provider">` field and its label entirely
- [x] 3.4 In `admin/auth_config.php` form HTML: add an `icon_url` URL `<input>` field (optional, type="url", pre-populated with `h($config['icon_url'] ?? '')`)
- [x] 3.5 In `admin/auth_config.php` POST save handler: read `$_POST['icon_url']`, trim it, pass `'' === $val ? null : $val` as `icon_url` in `$data`
- [x] 3.6 In `admin/auth_config.php` form HTML: remove the `pkce_enabled` checkbox entirely

## 4. Login Page

- [x] 4.1 In `admin/login.php`: confirm the homegrown username/password `<form>` renders unconditionally (no `if` wrapping it based on provider state)
- [x] 4.2 In `admin/login.php`: add a visual separator (`<hr>` or Bootstrap `<div class="my-3 text-center text-muted">or</div>`) between the homegrown form and the SSO button area
- [x] 4.3 In `admin/login.php`: change the SSO button/link `href` (or `action`) to point to `/admin/auth/redirect.php`
- [x] 4.4 In `admin/login.php` SSO button markup: add `<?php if ($active['icon_url']): ?><img src="<?= h($active['icon_url']) ?>" alt="" width="20" height="20"><?php endif; ?>` to the left of the label text inside the button element

## 5. OIDC Redirect Handler

- [x] 5.1 Create the `admin/auth/` directory
- [x] 5.2 Create `admin/auth/redirect.php` with `declare(strict_types=1)` and `require_once` for `../../config.php`, `../../includes/auth.php`, and `../../includes/db_auth_config.php`
- [x] 5.3 Call `auth_config_get()`; if result is `null` or `is_active = 0` set `$_SESSION['flash']` error and `header('Location: /admin/login.php')` + `exit`
- [x] 5.4 Generate `$state = bin2hex(random_bytes(16))` and `$nonce = bin2hex(random_bytes(16))`; store `$_SESSION['oidc'] = ['state' => $state, 'nonce' => $nonce]`
- [x] 5.5 Determine `$redirect_uri`: use `$config['redirect_uri']` when non-null; otherwise construct from `$_SERVER` as `(https if port 443 else http)://$_SERVER['HTTP_HOST']/admin/auth/callback.php`
- [x] 5.6 Build `$params` array: `response_type=code`, `client_id`, `redirect_uri`, `scope` (from `$config['scopes']`), `state`, `nonce`
- [x] 5.7 Always generate PKCE (no toggle): `$verifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=')`, compute `$challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')`, store `$_SESSION['oidc']['code_verifier'] = $verifier`, add `code_challenge_method=S256` and `code_challenge` to `$params`
- [x] 5.8 Build final URL: `rtrim($config['issuer_url'], '/') . '/authorize?' . http_build_query($params)`; send `header('Location: ' . $url)` and `exit`

## 6. OIDC Callback Placeholder

- [x] 6.1 Create `admin/auth/callback.php` with `declare(strict_types=1)` and `require_once` for `../../config.php` only — NO `require_auth()` call
- [x] 6.2 Render a minimal HTML page (using the admin layout or standalone) containing the text "SSO callback not yet implemented" and a `<a href="/admin/login.php">Return to login</a>` link
- [x] 6.3 Verify the file writes nothing to `$_SESSION['auth']` and does not read or act on `$_GET['code']`, `$_GET['state']`, or any other query parameter

## 7. Sidebar Accordion

- [x] 7.1 In `admin/_layout.php`: replace the `<aside><nav class="nav flex-column">` sidebar with a `<aside><div class="accordion accordion-flush" id="sidebarAccordion">` wrapper
- [x] 7.2 Add Dashboard and Profile as plain `<a class="nav-link">` links outside the accordion items (visible to all roles); retain existing active-link highlighting logic
- [x] 7.3 Add an Authorization accordion item block wrapped in `<?php if (in_array($role, ['sa', 'admin'])): ?>`; include Bootstrap accordion button and collapse div with `id="authMenu"`
- [x] 7.4 Inside the Authorization collapse div: add a Users `<a>` link to `/admin/users.php` (shown to both `sa` and `admin`)
- [x] 7.5 Inside the Authorization collapse div: add a Settings `<a>` link to `/admin/auth_config.php` wrapped in `<?php if ($role === 'sa'): ?>`
- [x] 7.6 Set the Bootstrap `show` class on the `#authMenu` collapse div and `aria-expanded="true"` on its button when `in_array($page, ['users', 'auth_config'])`

## 8. Users Placeholder Page

- [x] 8.1 Create `admin/users.php`: `declare(strict_types=1)`, `require_once` config + auth includes, `require_auth('sa', 'admin')`, `$page = 'users'`, include `_layout.php`, render `<h2>Users</h2>` and `<p class="text-muted">User management is coming soon.</p>`, include `_layout_end.php`
