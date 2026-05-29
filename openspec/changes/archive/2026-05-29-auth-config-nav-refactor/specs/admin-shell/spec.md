## MODIFIED Requirements

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

## ADDED Requirements

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
