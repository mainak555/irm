## Requirements

### Requirement: Database connection via environment
The database connection SHALL be configured exclusively from environment variables. `config.php` SHALL expose `db_dsn()`, `db_user()`, and `db_pass()` built from `env()`. `includes/db.php` SHALL expose the `db(): PDO` singleton using those helpers. No admin page or `db_*.php` file SHALL read environment variables directly. `config.php` SHALL start the PHP session (with a `session_status()` guard to prevent double-start), making sessions available to all admin pages.

#### Scenario: DB credentials come from environment only
- **WHEN** the admin application connects to the database
- **THEN** credentials are sourced from `env()` in `config.php` — not from any PHP file committed to version control

#### Scenario: Single PDO instance per request
- **WHEN** multiple `db_*.php` files call `db()` in the same request
- **THEN** only one PDO connection is created (static singleton in `includes/db.php`)

#### Scenario: No double session_start
- **WHEN** a page includes both `auth.php` and `config.php` (directly or via `db.php`) in any order
- **THEN** `session_start()` is called exactly once (both files guard with `session_status()` check)

---

### Requirement: Admin layout wraps all protected pages
Every protected admin page SHALL include `admin/_layout.php` at the top and `admin/_layout_end.php` at the bottom. The layout SHALL render: a top navbar, a left sidebar, a flash message area, and a main content `<div>`. The layout SHALL load Bootstrap 5.3 from CDN followed by `admin/style.css`. The layout SHALL set `data-bs-theme` on the `<html>` element based on the current user's theme preference before Bootstrap loads.

#### Scenario: Protected page renders within admin chrome
- **WHEN** an authenticated user loads any protected admin page
- **THEN** the response includes the top navbar, left sidebar, and main content area from `_layout.php`

#### Scenario: Bootstrap CDN link appears before custom stylesheet
- **WHEN** any admin page is rendered
- **THEN** the HTML `<head>` contains a Bootstrap 5.3 CDN `<link>` followed by the `admin/style.css` `<link>`

---

### Requirement: Role-aware sidebar navigation
The sidebar SHALL be implemented as a Bootstrap 5 accordion. It SHALL always contain a plain Dashboard link and a plain Profile link visible to all authenticated roles. It SHALL contain an Authorization accordion section visible only to users with `role = 'sa'` or `role = 'admin'`. The Authorization section SHALL contain a Users item linking to `/admin/users.php` and a Settings item linking to `/admin/auth_config.php`. The Settings item SHALL be rendered only when `role = 'sa'`. The Authorization accordion section SHALL be in the expanded (open) state when the current page is `users` or `auth_config`; it SHALL be collapsed for all other pages.

#### Scenario: SA sees full sidebar with Authorization section
- **WHEN** a user with `role = 'sa'` views any admin page
- **THEN** the sidebar contains a Dashboard link, a Profile link, and an Authorization accordion section
- **THEN** the Authorization section contains both a Users link and a Settings link

#### Scenario: Admin sees Authorization section without Settings
- **WHEN** a user with `role = 'admin'` views any admin page
- **THEN** the sidebar contains a Dashboard link, a Profile link, and an Authorization accordion section
- **THEN** the Authorization section contains a Users link
- **THEN** the Authorization section does NOT contain a Settings link

#### Scenario: Non-admin role sees no Authorization section
- **WHEN** a user with `role = 'faculty'` or `role = 'user'` views any admin page
- **THEN** the sidebar contains a Dashboard link and a Profile link
- **THEN** no Authorization accordion section is present in the sidebar

#### Scenario: Authorization accordion is expanded on the Users page
- **WHEN** an authenticated SA or admin user is on `/admin/users.php`
- **THEN** the Authorization accordion section is in the expanded/open state

#### Scenario: Authorization accordion is expanded on the Settings page
- **WHEN** an authenticated SA user is on `/admin/auth_config.php`
- **THEN** the Authorization accordion section is in the expanded/open state

#### Scenario: Authorization accordion is collapsed on the Dashboard
- **WHEN** an authenticated SA or admin user is on `/admin/index.php`
- **THEN** the Authorization accordion section is in the collapsed state

---

### Requirement: Theme switching
`/admin/profile.php` SHALL provide a theme selector with options: Light, Dark, System. On POST, the system SHALL update `auth_users.theme` and `$_SESSION['auth']['theme']` to the selected value, then redirect back to `profile.php`. The layout SHALL apply `data-bs-theme` based on the session value. For `system`, a synchronous `<script>` in `<head>` SHALL read `prefers-color-scheme` and set the attribute before Bootstrap loads, preventing flash of unstyled content.

#### Scenario: User switches to dark theme
- **WHEN** an authenticated user submits the theme switcher selecting 'dark'
- **THEN** `auth_users.theme` is set to `'dark'` and `$_SESSION['auth']['theme']` is updated
- **THEN** the page re-renders with `data-bs-theme="dark"` on the `<html>` element

#### Scenario: System theme respects OS preference
- **WHEN** a user's theme is `'system'` and the page renders
- **THEN** `data-bs-theme` is set synchronously in `<head>` to `'dark'` or `'light'` matching the browser's `prefers-color-scheme` before Bootstrap loads

#### Scenario: Theme preference persists across sessions
- **WHEN** a user logs out and logs back in
- **THEN** the theme loaded from `auth_users.theme` is applied, not the browser default

---

### Requirement: Flash messages rendered once and cleared
The layout SHALL check `$_SESSION['flash']` at render time. If present, the message SHALL be displayed in the layout's flash area with the appropriate style (`ok` = success, `err` = danger). Immediately after rendering, `$_SESSION['flash']` SHALL be unset so the message does not appear on subsequent page loads.

#### Scenario: Flash message displayed after an action
- **WHEN** `$_SESSION['flash']` contains a message and an admin page renders
- **THEN** the message is displayed in the layout's flash area

#### Scenario: Flash message not repeated on next page load
- **WHEN** the user navigates to another admin page after a flash message was shown
- **THEN** no flash message is displayed (the session key was cleared)

---

### Requirement: CSRF protection on all POST forms
`admin/_layout.php` SHALL generate `$_SESSION['csrf']` once (if not already set) so that every protected page that uses the layout gets a token automatically. `admin/login.php` SHALL generate its own token because it does not use the layout. Every form SHALL embed the token as a hidden `<input name="csrf">`. Every POST handler SHALL compare the submitted token against `$_SESSION['csrf']` using `hash_equals()`. A mismatch or missing token SHALL abort the request with HTTP 403 and no data modification. The token SHALL be rotated after any authentication state change (setup completion, successful login).

#### Scenario: POST with matching CSRF token is processed
- **WHEN** a user submits a form with a CSRF token that matches `$_SESSION['csrf']`
- **THEN** the form action is processed normally

#### Scenario: POST with mismatched CSRF token is rejected
- **WHEN** a POST request arrives with a CSRF token that does not match the session value
- **THEN** the request is rejected with HTTP 403 and no database changes are made

#### Scenario: POST with missing CSRF token is rejected
- **WHEN** a POST request arrives with no `csrf` field
- **THEN** the request is rejected with HTTP 403 and no database changes are made

#### Scenario: CSRF token available on every layout-wrapped page
- **WHEN** any protected admin page that includes `_layout.php` renders a form
- **THEN** `$_SESSION['csrf']` is already set by the layout before the form HTML is output

#### Scenario: Token rotated after authentication state change
- **WHEN** the setup form is submitted successfully or the login form authenticates the user
- **THEN** a new `$_SESSION['csrf']` token is generated before the redirect

---

### Requirement: Users placeholder page
`/admin/users.php` SHALL call `require_auth('sa', 'admin')`. The page SHALL render a heading "Users" and a notice communicating that user management is coming soon. The page SHALL NOT render any table, form, or data rows.

#### Scenario: Unauthenticated visitor redirected to login
- **WHEN** an unauthenticated visitor navigates to `/admin/users.php`
- **THEN** they are redirected to `/admin/login.php`

#### Scenario: Insufficient role receives 403
- **WHEN** a user with `role = 'faculty'` navigates to `/admin/users.php`
- **THEN** the `admin/403.php` page is rendered with HTTP status 403

#### Scenario: Admin sees the placeholder page
- **WHEN** a user with `role = 'admin'` navigates to `/admin/users.php`
- **THEN** the page renders with a "Users" heading and a coming-soon notice
- **THEN** no table or form is present in the response

#### Scenario: SA sees the placeholder page
- **WHEN** a user with `role = 'sa'` navigates to `/admin/users.php`
- **THEN** the page renders with a "Users" heading and a coming-soon notice
