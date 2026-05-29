## 1. Cleanup — Remove Old Files

- [x] 1.1 Delete all existing files under `admin/` (index.php, _layout.php, _layout_end.php, login.php, logout.php, settings.php, menus.php, links.php, news.php, slides.php, blocks.php, style.css)
- [x] 1.2 Delete all existing files under `sql/` (all previous migration files)

## 2. Database — Ground Truth Schema

- [x] 2.1 Create `sql/schema.sql` — add `CREATE TABLE auth_users` with columns: id, username (nullable unique), email (nullable unique), name, role ENUM(sa,admin,faculty,user), password (nullable), is_active, theme ENUM(light,dark,system), created_at, updated_at
- [x] 2.2 Add `CREATE TABLE auth_config` to `sql/schema.sql` with columns: id, provider ENUM(google,okta), label, logo_url (nullable), type ENUM(OIDC,SAML), issuer_url, client_id, client_secret, pkce_enabled, scopes, redirect_uri (nullable), is_active, created_at, updated_at
- [x] 2.3 Content tables (menus, pages, content_blocks, hero_slides, popular_links, news) are managed separately — `sql/schema.sql` intentionally contains auth tables only
- [ ] 2.4 Run `sql/schema.sql` against the database to apply the new ground truth

## 3. DB Functions — Login Domain

- [x] 3.1 Create `includes/db_login.php` with `auth_user_count(): int` — returns total row count from auth_users
- [x] 3.2 Add `auth_user_find_by_username(string $username): ?array` to db_login.php — looks up by username column, returns row or null
- [x] 3.3 Add `auth_user_create_sa(string $password_hash): void` to db_login.php — inserts the sa row with hardcoded username='admin', name='Administrator', role='sa'
- [x] 3.4 Add `auth_config_active(): ?array` to db_login.php — returns the auth_config row where is_active=1, or null

## 4. DB Functions — Profile Domain

- [x] 4.1 Create `includes/db_profile.php` with `auth_user_find_by_id(int $id): ?array` — fetches full user row by id
- [x] 4.2 Add `auth_user_update_password(int $id, string $hash): void` to db_profile.php — updates password column for given id
- [x] 4.3 Add `auth_user_update_theme(int $id, string $theme): void` to db_profile.php — updates theme column for given id

## 5. DB Functions — OIDC Config Domain

- [x] 5.1 Create `includes/db_auth_config.php` with `auth_config_get(): ?array` — returns the single auth_config row or null
- [x] 5.2 Add `auth_config_save(array $data): void` to db_auth_config.php — DELETEs all rows then INSERTs a new row using named placeholders
- [x] 5.3 Add `auth_config_clear(): void` to db_auth_config.php — DELETEs all rows from auth_config
- [x] 5.4 Add `auth_config_toggle(): void` to db_auth_config.php — flips is_active between 0 and 1 for the existing row

## 6. Auth Layer

- [x] 6.1 Create `includes/auth.php` with `require_auth(string ...$roles): void` — checks $_SESSION['auth'], redirects to /admin/login.php if absent, renders admin/403.php with http_response_code(403) if role not in allowed list
- [x] 6.2 Add `current_user(): ?array` to auth.php — returns $_SESSION['auth'] or null

## 7. Admin Shell — Layout & Static Pages

- [x] 7.1 Create `admin/403.php` — minimal 403 page with message, link back to dashboard, no layout wrapper (avoid redirect loop)
- [x] 7.2 Create `admin/style.css` — Bootstrap overrides, sidebar layout, role badge styles, theme-aware CSS custom properties
- [x] 7.3 Create `admin/_layout.php` — outputs `<!DOCTYPE html>`, `<html data-bs-theme="...">` set from session theme (with inline `<script>` for system theme using prefers-color-scheme before Bootstrap loads), `<head>` with Bootstrap 5.3 CDN link then style.css, top navbar (app name, user name, role badge, theme switcher form, logout link), left sidebar with role-filtered links (Dashboard always, Auth Config for sa only), flash message render + session clear, opens main content `<div>`
- [x] 7.4 Create `admin/_layout_end.php` — closes main content `<div>`, `</body>`, `</html>`

## 8. Login, Setup & Logout

- [x] 8.1 Create `admin/login.php` — at top: call `auth_user_count()`; if 0, show setup form (password + confirm only, CSRF token); if >0, show login form (username + password, CSRF token) and optionally OIDC button from `auth_config_active()`
- [x] 8.2 Add setup POST handler to login.php — validate CSRF, validate password complexity (regex: `/^(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/`), call `auth_user_create_sa(password_hash(..., PASSWORD_BCRYPT))`, redirect to login
- [x] 8.3 Add login POST handler to login.php — validate CSRF, call `auth_user_find_by_username()`, verify password with `password_verify()`, check is_active=1, populate `$_SESSION['auth']` with id/name/role/theme, redirect to /admin/index.php; on any failure show generic error
- [x] 8.4 Create `admin/logout.php` — call `session_destroy()`, redirect to /admin/login.php

## 9. Dashboard

- [x] 9.1 Create `admin/index.php` — require_once auth.php, call require_auth(), include _layout.php, render minimal welcome message with user name and role badge, include _layout_end.php

## 10. Profile Page

- [x] 10.1 Create `admin/profile.php` — require_auth(), include _layout.php, render two forms: (a) change password form with current/new/confirm fields and CSRF token, (b) theme selector form with Light/Dark/System options and CSRF token
- [x] 10.2 Add password change POST handler to profile.php — validate CSRF, call `auth_user_find_by_id()`, verify current password with `password_verify()`, validate new password complexity, call `auth_user_update_password()`, set flash success, redirect
- [x] 10.3 Add theme change POST handler to profile.php — validate CSRF, validate theme value is in ('light','dark','system'), call `auth_user_update_theme()`, update `$_SESSION['auth']['theme']`, set flash success, redirect

## 11. OIDC Config Page

- [x] 11.1 Create `admin/auth_config.php` — require_auth('sa'), include _layout.php, call `auth_config_get()`, render single config form pre-filled with existing values (client_secret always as `<input type="password">`), provider dropdown (google, okta), is_active toggle, CSRF token
- [x] 11.2 Add save POST handler to auth_config.php — validate CSRF, validate required fields (provider, type, issuer_url, client_id, client_secret non-empty), call `auth_config_save()`, set flash success, redirect
- [x] 11.3 Add clear POST handler to auth_config.php — validate CSRF, call `auth_config_clear()`, set flash success, redirect
- [x] 11.4 Add toggle POST handler to auth_config.php — validate CSRF, call `auth_config_toggle()`, set flash message with new state, redirect

## 12. Verify & Test

- [ ] 12.1 Visit /admin/ with empty auth_users — confirm setup form appears, not login form
- [ ] 12.2 Submit weak passwords on setup form — confirm each complexity rule is enforced individually
- [ ] 12.3 Complete first-launch setup — confirm sa row created, redirected to login
- [ ] 12.4 Log in as admin — confirm session populated, redirect to dashboard
- [ ] 12.5 Test theme switching (light/dark/system) — confirm DB updated, page re-renders with correct data-bs-theme
- [ ] 12.6 Test password change on profile — confirm old password rejected, new password accepted and stored
- [ ] 12.7 Navigate to /admin/auth_config.php — confirm 403 if logged in as non-sa, confirm form loads for sa
- [ ] 12.8 Save, clear, and toggle OIDC config — confirm singleton behaviour (only 1 row ever exists after save)
- [ ] 12.9 Enable OIDC provider — reload login page and confirm SSO button appears; disable and confirm it disappears
- [ ] 12.10 Test CSRF — submit a form with a tampered token, confirm 403 and no data change
