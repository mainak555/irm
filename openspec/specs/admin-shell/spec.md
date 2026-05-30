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
Every protected admin page SHALL include `admin/_layout.php` at the top and `admin/_layout_end.php` at the bottom. The layout SHALL render: a top navbar, a left sidebar, a flash message area, and a main content `<div>`. The layout SHALL load Bootstrap 5.3 from CDN followed by `assets/css/admin.css`. The layout SHALL set `data-bs-theme` on the `<html>` element based on the current user's theme preference before Bootstrap loads.

#### Scenario: Protected page renders within admin chrome
- **WHEN** an authenticated user loads any protected admin page
- **THEN** the response includes the top navbar, left sidebar, and main content area from `_layout.php`

#### Scenario: Bootstrap CDN link appears before custom stylesheet
- **WHEN** any admin page is rendered
- **THEN** the HTML `<head>` contains a Bootstrap 5.3 CDN `<link>` followed by the `assets/css/admin.css` `<link>`

---

### Requirement: Role-aware sidebar navigation
The sidebar SHALL be implemented as a Bootstrap 5 accordion. It SHALL always contain a plain Dashboard link and a plain Profile link visible to all authenticated roles. It SHALL contain an **Authorization** accordion section visible only to users with `role = 'sa'` or `role = 'admin'`. The Authorization section SHALL contain a Users item linking to `/admin/users.php` and a **SSO** item linking to `/admin/auth_config.php`. The SSO item SHALL be rendered only when `role = 'sa'`. It SHALL contain a **Settings** accordion section visible only to users with `role = 'sa'`. The Settings section SHALL contain a **General** item linking to `/admin/config_general.php`. The Authorization accordion section SHALL be in the expanded state when the current page is `users` or `auth_config`; collapsed otherwise. The Settings accordion section SHALL be in the expanded state when the current page is any `config_*` page; collapsed otherwise.

#### Scenario: SA sees full sidebar including both accordions
- **GIVEN** a user with role `sa` is authenticated
- **WHEN** they view any admin page
- **THEN** the sidebar SHALL contain a Dashboard link and a Profile link
- **AND** the sidebar SHALL contain an Authorization accordion with Users and SSO links
- **AND** the sidebar SHALL contain a Settings accordion with a General link

#### Scenario: Admin sees Authorization accordion but no Settings accordion
- **GIVEN** a user with role `admin` is authenticated
- **WHEN** they view any admin page
- **THEN** the sidebar SHALL contain an Authorization accordion with a Users link
- **AND** the Authorization accordion SHALL NOT contain an SSO link
- **AND** no Settings accordion SHALL be present in the sidebar

#### Scenario: Non-admin roles see no accordions
- **GIVEN** a user with role `faculty` or `user` is authenticated
- **WHEN** they view any admin page
- **THEN** no Authorization accordion SHALL be present in the sidebar
- **AND** no Settings accordion SHALL be present in the sidebar

#### Scenario: Authorization accordion labels SSO sub-item correctly
- **GIVEN** a user with role `sa` or `admin` is authenticated
- **WHEN** they view any admin page
- **THEN** the Authorization accordion SHALL contain a sub-item labelled "SSO"
- **AND** the Authorization accordion SHALL NOT contain a sub-item labelled "Settings" or "Configurations"

#### Scenario: Settings accordion expands on General page
- **GIVEN** a user with role `sa` is authenticated
- **WHEN** they open the General settings page (`/admin/config_general.php`)
- **THEN** the Settings accordion SHALL be in the expanded state
- **AND** the Authorization accordion SHALL be in the collapsed state

#### Scenario: Authorization accordion is expanded on the Users page
- **WHEN** an authenticated SA or admin user is on `/admin/users.php`
- **THEN** the Authorization accordion section SHALL be in the expanded state

#### Scenario: Authorization accordion is expanded on the SSO page
- **WHEN** an authenticated SA user is on `/admin/auth_config.php`
- **THEN** the Authorization accordion section SHALL be in the expanded state

#### Scenario: Both accordions collapsed on Dashboard
- **WHEN** an authenticated SA user is on `/admin/index.php`
- **THEN** the Authorization accordion SHALL be in the collapsed state
- **AND** the Settings accordion SHALL be in the collapsed state

---

### Requirement: Theme switching
The system SHALL provide theme switching via two surfaces: a dropdown in the top navbar (available on every page) and a form on `/admin/profile.php`. Both submit to `/admin/profile.php` with `action=theme`. On POST, the system SHALL update `auth_users.theme` and `$_SESSION['auth']['theme']` to the selected value. The navbar form SHALL pass the originating page URL as a `redirect` parameter and the handler SHALL redirect back to that URL; the profile form SHALL redirect back to `/admin/profile.php`. The layout SHALL apply `data-bs-theme` based on the session value. For `system`, a synchronous `<script>` in `<head>` SHALL read `prefers-color-scheme` and set the attribute before Bootstrap loads, preventing flash of unstyled content.

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

### Requirement: UTC timestamp display utility
`admin/_layout.php` SHALL include a synchronous `<script>` in `<head>` that defines `window.IRM.formatUtcTs(isoStr)`. This function SHALL accept a MySQL UTC timestamp string (`YYYY-MM-DD HH:MM:SS`) and return it formatted in the browser's local timezone using `Intl.DateTimeFormat` with day, month, year, hour, and minute components — without any inline timezone label. On `DOMContentLoaded`, the layout SHALL query all elements carrying a `data-utc-ts` attribute, call `formatUtcTs` on the attribute value, replace the element's `textContent`, and set the element's `title` attribute to `"[local formatted time] ([IANA tz name])  ·  [raw UTC value] UTC"`. This utility is the sole mechanism for timestamp display across the entire admin panel; individual pages SHALL NOT implement their own timestamp formatting. See ADR-0015.

#### Scenario: Timestamp element is formatted on page load
- **WHEN** any admin page renders an element with `data-utc-ts="2026-01-15 09:00:00"`
- **THEN** after `DOMContentLoaded` the element's text is the local-timezone equivalent (e.g. `15 Jan 2026, 14:30`)
- **THEN** the element's `title` tooltip contains the local time, the IANA timezone (e.g. `Asia/Kolkata`), and the raw UTC string

#### Scenario: Timezone label is not rendered inline
- **WHEN** any `data-utc-ts` element is formatted
- **THEN** the element's visible text contains no timezone label, offset, or abbreviation

#### Scenario: Utility available globally before page JS runs
- **WHEN** any admin page loads
- **THEN** `window.IRM.formatUtcTs` is callable before the page's own `<script>` blocks execute (defined in `<head>`)

---

### Requirement: Viewport-locked admin shell
The admin chrome SHALL use a fixed-position layout so the navbar and sidebar remain
permanently visible while only the main content area scrolls. See ADR-0021.

The `.navbar` SHALL carry `position: fixed; top: 0; left: 0; right: 0; z-index: 1030`.
`.admin-wrapper` SHALL be `position: fixed; top: 56px; left: 0; right: 0; bottom: 0` —
filling the viewport below the navbar exactly. `.admin-sidebar` SHALL have
`height: 100%; overflow-y: auto`. `.admin-main` SHALL have `overflow-y: auto; height: 100%`
and SHALL be the sole scrolling region for page content.

#### Scenario: Navbar stays visible while content scrolls
- **GIVEN** an admin page whose content exceeds the viewport height
- **WHEN** the user scrolls down in the main content area
- **THEN** the top navbar SHALL remain fixed at the top of the viewport
- **AND** the user SHALL NOT need to scroll back up to access the navbar

#### Scenario: Sidebar stays visible while content scrolls
- **GIVEN** an admin page whose content exceeds the viewport height
- **WHEN** the user scrolls down in the main content area
- **THEN** the left sidebar SHALL remain fixed and fully visible
- **AND** all navigation links SHALL remain accessible without scrolling

#### Scenario: Page body does not scroll
- **WHEN** any protected admin page renders
- **THEN** the `document` (viewport scroll) SHALL NOT move — only `.admin-main` scrolls

