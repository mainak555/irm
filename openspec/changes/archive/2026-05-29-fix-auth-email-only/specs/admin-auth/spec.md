## MODIFIED Requirements

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

## REMOVED Requirements

### Requirement: SA username and password login
**Reason**: Replaced by "Email-based login for all accounts". The `username` column is dropped; all authentication now uses the `email` column. The SA account uses the reserved sentinel `email='admin'`.
**Migration**: Existing SA accounts must have `email` set to `'admin'` before the `username` column is dropped. Run: `UPDATE auth_users SET email = 'admin' WHERE username = 'admin' AND role = 'sa';`
