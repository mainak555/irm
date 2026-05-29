## Requirements

### Requirement: First-launch setup screen
When the `auth_users` table has zero rows, the system SHALL display a setup screen instead of the login form. The setup screen SHALL collect only a password for the built-in admin account. On submission, the system SHALL create one `auth_users` row with `email='admin'`, `name='Administrator'`, `role='sa'`, and the bcrypt-hashed password. The `username` column SHALL NOT exist. Only one `sa` account SHALL ever exist; the setup screen SHALL NOT appear once any row exists in `auth_users`.

#### Scenario: Setup screen shown on first visit
- **WHEN** a visitor navigates to any `/admin/` page and `auth_users` has zero rows
- **THEN** the password setup form is displayed instead of the login form

#### Scenario: SA account created on valid setup submission
- **WHEN** the visitor submits the setup form with a password that passes all complexity rules
- **THEN** one `auth_users` row is inserted with `email='admin'`, `name='Administrator'`, `role='sa'`, and the bcrypt hash stored in `password`
- **THEN** the visitor is redirected to `/admin/login.php`

#### Scenario: Setup screen does not appear once auth_users has rows
- **WHEN** `auth_users` contains at least one row and a visitor loads `/admin/login.php`
- **THEN** the login form is displayed, not the setup form

---

### Requirement: Password complexity validation
All passwords submitted to the system (during setup and password change) SHALL pass: minimum 8 characters, at least one uppercase letter, at least one digit, and at least one special character. Validation SHALL be enforced server-side. Failing passwords SHALL be rejected before any database write.

#### Scenario: Password rejected when too short
- **WHEN** a password shorter than 8 characters is submitted
- **THEN** the form is re-displayed with an error and no account is created or updated

#### Scenario: Password rejected when missing required character class
- **WHEN** a password is submitted that is missing any of: uppercase letter, digit, or special character
- **THEN** the form is re-displayed with an error describing the unmet requirement

#### Scenario: Password accepted when all rules are satisfied
- **WHEN** a password of 8+ characters containing uppercase, digit, and special character is submitted
- **THEN** the password is accepted and the operation proceeds

---

### Requirement: Email-based login for all accounts
The login form SHALL present a single field labelled "Email / Username" and a password field. The system SHALL look up the user by the submitted value against the `email` column using `auth_user_find_by_email()`. The SA account SHALL be found by submitting `admin`; all other accounts SHALL be found by submitting their registered email address. The system SHALL verify the submitted password against the stored bcrypt hash using `password_verify()`, and check that `is_active = 1`. On success, the system SHALL write `$_SESSION['auth']` containing `id`, `name`, `role`, and `theme`, then redirect to `/admin/index.php`. The `username` column SHALL NOT be read or written at any point in this flow.

#### Scenario: SA logs in with identifier 'admin' and correct password
- **WHEN** the SA user types `admin` in the Email / Username field and submits the correct password
- **THEN** `$_SESSION['auth']` is populated with the SA's id, name, role, and theme
- **THEN** the user is redirected to `/admin/index.php`

#### Scenario: Non-SSO user logs in with email and correct password
- **WHEN** a non-SSO user types their registered email address in the Email / Username field and submits the correct password
- **THEN** `$_SESSION['auth']` is populated with that user's id, name, role, and theme
- **THEN** the user is redirected to `/admin/index.php`

#### Scenario: Login rejected with wrong password
- **WHEN** a login form is submitted with an incorrect password
- **THEN** the login page is re-displayed with a generic authentication error
- **THEN** no session is created

#### Scenario: Login rejected for unknown identifier
- **WHEN** a login form is submitted with a value that has no matching `email` row
- **THEN** the login page is re-displayed with a generic authentication error

#### Scenario: Inactive account cannot log in
- **WHEN** a user with `is_active=0` submits correct credentials
- **THEN** the login page is re-displayed with an error and no session is created

---

### Requirement: Session guard with role enforcement
Every protected admin page SHALL call `require_auth()` at its top. With no arguments it SHALL block unauthenticated users. With role arguments it SHALL additionally block authenticated users whose `role` is not in the allowed list. Blocked-unauthenticated users SHALL be redirected to `/admin/login.php`. Blocked-wrong-role users SHALL receive the `admin/403.php` response with HTTP 403.

#### Scenario: Unauthenticated visitor redirected to login
- **WHEN** a visitor with no active session requests any protected admin page
- **THEN** they are redirected to `/admin/login.php`

#### Scenario: Authenticated user with insufficient role sees 403
- **WHEN** a user with `role='admin'` requests a page that calls `require_auth('sa')`
- **THEN** the `admin/403.php` page is rendered with HTTP status 403

#### Scenario: Authenticated user with correct role accesses page
- **WHEN** a user with `role='sa'` requests a page that calls `require_auth('sa')`
- **THEN** the page content is rendered normally

---

### Requirement: Logout
Visiting `/admin/logout.php` SHALL call `session_destroy()`, clear the session cookie, and redirect to `/admin/login.php`. No content SHALL be rendered.

#### Scenario: User logs out and session is destroyed
- **WHEN** an authenticated user visits `/admin/logout.php`
- **THEN** the session is destroyed and they are redirected to `/admin/login.php`

#### Scenario: Already-logged-out user visiting logout is redirected
- **WHEN** an unauthenticated visitor visits `/admin/logout.php`
- **THEN** they are redirected to `/admin/login.php`

---

### Requirement: SA password change on profile page
`/admin/profile.php` SHALL allow the sa user to change their password. The form SHALL require the current password (verified via `password_verify()`), a new password, and a confirmation. The new password SHALL pass complexity validation. On success, the new bcrypt hash SHALL be written to `auth_users.password` and the session SHALL remain active.

#### Scenario: Password changed successfully
- **WHEN** the sa user submits the change-password form with the correct current password and a valid new password
- **THEN** the new bcrypt hash is stored in `auth_users.password`
- **THEN** a success flash message is displayed and the session remains active

#### Scenario: Password change rejected with wrong current password
- **WHEN** the sa user submits the change-password form with an incorrect current password
- **THEN** the form is re-displayed with an error and the password is not changed

#### Scenario: Password change rejected when new password fails complexity
- **WHEN** the sa user submits a new password that fails any complexity rule
- **THEN** the form is re-displayed with a validation error and the password is not changed

