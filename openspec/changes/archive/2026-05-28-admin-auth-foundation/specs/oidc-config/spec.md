## ADDED Requirements

### Requirement: SA-only access to OIDC configuration
`/admin/auth_config.php` SHALL call `require_auth('sa')`. Any authenticated user whose role is not `sa` SHALL receive the `admin/403.php` response with HTTP 403. Unauthenticated visitors SHALL be redirected to `/admin/login.php`.

#### Scenario: Non-sa authenticated user is denied access
- **WHEN** a user with `role='admin'` navigates to `/admin/auth_config.php`
- **THEN** the 403 page is displayed with HTTP status 403

#### Scenario: Unauthenticated visitor is redirected to login
- **WHEN** an unauthenticated visitor navigates to `/admin/auth_config.php`
- **THEN** they are redirected to `/admin/login.php`

#### Scenario: SA user can load the config page
- **WHEN** the sa user navigates to `/admin/auth_config.php`
- **THEN** the OIDC configuration form is rendered

---

### Requirement: View current provider configuration
When an `auth_config` row exists, the form SHALL be pre-filled with all saved values. The `client_secret` field SHALL always be rendered as `<input type="password">` — never as plain text — regardless of whether it contains a value. When no row exists, all fields SHALL be empty.

#### Scenario: Form pre-filled when config exists
- **WHEN** the sa user loads `/admin/auth_config.php` and an `auth_config` row exists
- **THEN** the form fields are populated with the saved provider, type, issuer URL, client ID, and scopes
- **THEN** the client_secret field is rendered as a masked password input

#### Scenario: Client secret never shown as plain text
- **WHEN** the sa user loads the config page regardless of whether a secret is stored
- **THEN** the client_secret field is always an `<input type="password">` element

#### Scenario: Empty form shown when no config exists
- **WHEN** the sa user loads `/admin/auth_config.php` and `auth_config` has zero rows
- **THEN** all form fields are empty and `is_active` defaults to inactive

---

### Requirement: Save provider configuration
Submitting the config form SHALL validate that `provider`, `type`, `issuer_url`, and `client_id` are non-empty. `client_secret` is required on first save (no existing row); on update (existing row present), leaving `client_secret` blank SHALL preserve the stored value rather than rejecting the save. On valid submission, the system SHALL DELETE all existing `auth_config` rows and INSERT a fresh row (singleton enforcement). On success, a flash message SHALL confirm the save.

#### Scenario: Valid config saved for the first time
- **WHEN** the sa user submits the form with all required fields including `client_secret` and no config row exists
- **THEN** a new `auth_config` row is inserted
- **THEN** a success flash message is displayed

#### Scenario: Existing config updated with new secret
- **WHEN** the sa user submits the form with a non-empty `client_secret` and a config row already exists
- **THEN** the existing row is replaced with the new values including the new secret

#### Scenario: Existing secret preserved when secret field left blank on update
- **WHEN** the sa user submits the form with `client_secret` left blank and a config row already exists
- **THEN** the existing `client_secret` value is carried over into the new row
- **THEN** a success flash message is displayed

#### Scenario: Save rejected when a required field is empty
- **WHEN** the sa user submits the form with any of `provider`, `type`, `issuer_url`, or `client_id` left blank, OR with `client_secret` blank on first save
- **THEN** the form is re-displayed with a validation error
- **THEN** the existing config row is unchanged

---

### Requirement: Clear provider configuration
The config page SHALL provide a Clear action that deletes all rows from `auth_config`. The clear action SHALL be a POST form submission (not a GET link). On success, a flash message SHALL confirm the configuration has been cleared.

#### Scenario: SA clears the provider configuration
- **WHEN** the sa user submits the clear action
- **THEN** all rows in `auth_config` are deleted
- **THEN** a flash message confirms the config has been cleared

#### Scenario: Cleared config results in empty form on next load
- **WHEN** the sa user loads the config page after clearing
- **THEN** all form fields are empty

---

### Requirement: Toggle provider active/inactive
The config page SHALL provide a Toggle action that flips `auth_config.is_active` between 0 and 1. A flash message SHALL confirm the new state. Toggling SHALL only be available when a config row exists.

#### Scenario: SA activates an inactive provider
- **WHEN** the sa user toggles the provider when `is_active=0`
- **THEN** `is_active` is set to 1
- **THEN** a flash message confirms the provider is now active

#### Scenario: SA deactivates an active provider
- **WHEN** the sa user toggles the provider when `is_active=1`
- **THEN** `is_active` is set to 0
- **THEN** a flash message confirms the provider is now inactive

---

### Requirement: Login page OIDC button state
`/admin/login.php` SHALL query `auth_config` for a row with `is_active=1`. When such a row exists, the page SHALL display an SSO login button below the username/password form, labelled with `auth_config.label`. When no active row exists (zero rows or `is_active=0`), no SSO button SHALL be rendered.

#### Scenario: OIDC button shown when provider is active
- **WHEN** a visitor loads `/admin/login.php` and an `auth_config` row with `is_active=1` exists
- **THEN** an SSO login button is displayed below the username/password form using the provider's `label`

#### Scenario: OIDC button hidden when no active provider
- **WHEN** a visitor loads `/admin/login.php` and either `auth_config` has zero rows or `is_active=0`
- **THEN** no SSO button is displayed and only the username/password form is shown
