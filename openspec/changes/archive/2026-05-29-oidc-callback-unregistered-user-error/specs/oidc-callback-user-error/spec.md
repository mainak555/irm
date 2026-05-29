## ADDED Requirements

### Requirement: OIDC provisioning failure redirects to dedicated error page
When the OIDC callback completes token validation successfully but the authenticated email is not present in `auth_users`, or the user row exists but `role` is empty or NULL, the system SHALL store a descriptive message in `$_SESSION['oidc_provision_error']` and redirect the browser to `/admin/auth/error.php` with HTTP 302. The existing `oidc_fail()` path (used for protocol and token errors) SHALL NOT be used for these two cases.

#### Scenario: Unregistered email redirects to error page
- **WHEN** the OIDC callback successfully validates the id_token and the extracted email has no matching row in `auth_users`
- **THEN** `$_SESSION['oidc_provision_error']` is set to a message identifying the email is not registered
- **THEN** the browser is redirected to `/admin/auth/error.php`
- **THEN** `$_SESSION['auth']` is NOT set

#### Scenario: User with empty role redirects to error page
- **WHEN** the OIDC callback successfully validates the id_token and `auth_user_find_by_email` returns a row whose `role` field is empty or NULL
- **THEN** `$_SESSION['oidc_provision_error']` is set to a message indicating no role is assigned
- **THEN** the browser is redirected to `/admin/auth/error.php`
- **THEN** `$_SESSION['auth']` is NOT set

#### Scenario: Protocol errors still redirect to login page
- **WHEN** the OIDC callback encounters a token validation failure (state mismatch, expired id_token, nonce mismatch, token endpoint error, etc.)
- **THEN** the browser is redirected to `/admin/login.php` via the existing `oidc_fail()` path with a flash error
- **THEN** `/admin/auth/error.php` is NOT involved

---

### Requirement: OIDC error page renders without an authenticated session
`/admin/auth/error.php` SHALL be publicly accessible without a session guard. It SHALL read `$_SESSION['oidc_provision_error']`, display its value as the primary error message, and immediately unset the key. When the key is absent (e.g., direct navigation or page refresh), the page SHALL display a generic fallback message. The page SHALL instruct the user to contact an administrator. The page SHALL provide a link back to `/admin/login.php`.

#### Scenario: Error page shows provisioning message from session
- **WHEN** a browser is redirected to `/admin/auth/error.php` and `$_SESSION['oidc_provision_error']` contains a message
- **THEN** the page renders that message as the primary error text
- **THEN** `$_SESSION['oidc_provision_error']` is unset after rendering
- **THEN** the page instructs the user to contact an administrator

#### Scenario: Error page shows generic fallback on direct navigation
- **WHEN** a visitor navigates directly to `/admin/auth/error.php` with no `$_SESSION['oidc_provision_error']` key present
- **THEN** the page renders a generic message such as "Access could not be completed"
- **THEN** the page instructs the user to contact an administrator

#### Scenario: Error page provides link back to login
- **WHEN** any visitor loads `/admin/auth/error.php`
- **THEN** the response contains a link to `/admin/login.php`

#### Scenario: Error page accessible without a session
- **WHEN** an unauthenticated visitor navigates to `/admin/auth/error.php`
- **THEN** the page renders without redirecting to the login page
- **THEN** no `$_SESSION['auth']` is created
