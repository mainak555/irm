## Requirements

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
When an `auth_config` row exists, the form SHALL be pre-filled with all saved values. The form SHALL NOT render a provider dropdown. The form SHALL render an `icon_url` field (URL text input, optional) pre-populated with the saved `icon_url` value when one exists, and empty when `icon_url` is NULL. The `client_secret` field SHALL always be rendered as `<input type="password">` — never as plain text — regardless of whether it contains a value. When no row exists, all fields SHALL be empty.

#### Scenario: Form pre-filled when config exists
- **WHEN** the SA user loads `/admin/auth_config.php` and an `auth_config` row exists
- **THEN** the form fields are populated with the saved type, issuer URL, client ID, scopes, and icon URL
- **THEN** the client_secret field is rendered as a masked password input

#### Scenario: Provider dropdown is absent from the form
- **WHEN** the SA user loads `/admin/auth_config.php`
- **THEN** no provider dropdown (`<select name="provider">`) is present in the response

#### Scenario: Icon URL field shown and pre-populated
- **WHEN** the SA user loads `/admin/auth_config.php` and the saved `icon_url` is `https://example.com/icon.png`
- **THEN** the icon URL input field contains `https://example.com/icon.png`

#### Scenario: Icon URL field empty when not saved
- **WHEN** the SA user loads `/admin/auth_config.php` and `auth_config.icon_url` is NULL
- **THEN** the icon URL input field is empty

#### Scenario: Client secret never shown as plain text
- **WHEN** the SA user loads the config page regardless of whether a secret is stored
- **THEN** the client_secret field is always an `<input type="password">` element

#### Scenario: Empty form shown when no config exists
- **WHEN** the SA user loads `/admin/auth_config.php` and `auth_config` has zero rows
- **THEN** all form fields are empty and `is_active` defaults to inactive

---

### Requirement: Save provider configuration
Submitting the config form SHALL validate that `type`, `issuer_url`, and `client_id` are non-empty. `provider` SHALL NOT be a required or collected field. `client_secret` is required on first save (no existing row); on update (existing row present), leaving `client_secret` blank SHALL preserve the stored value rather than rejecting the save. `icon_url` is optional; when supplied it SHALL be stored as-is; when left blank it SHALL be stored as NULL. On valid submission the system SHALL DELETE all existing `auth_config` rows and INSERT a fresh row. On success a flash message SHALL confirm the save.

#### Scenario: Valid config saved for the first time without icon URL
- **WHEN** the SA user submits the form with `type`, `issuer_url`, `client_id`, and `client_secret` filled and `icon_url` left blank, and no config row exists
- **THEN** a new `auth_config` row is inserted with `icon_url = NULL`
- **THEN** a success flash message is displayed

#### Scenario: Valid config saved with an icon URL
- **WHEN** the SA user submits the form with all required fields and `icon_url` set to `https://cdn.example.com/logo.svg`
- **THEN** `auth_config.icon_url` is stored as `https://cdn.example.com/logo.svg`

#### Scenario: Icon URL cleared when field is submitted blank on update
- **WHEN** the SA user submits the form with `icon_url` left blank and a config row already exists with a non-NULL `icon_url`
- **THEN** `auth_config.icon_url` is stored as NULL

#### Scenario: Existing secret preserved when secret field left blank on update
- **WHEN** the SA user submits the form with `client_secret` left blank and a config row already exists
- **THEN** the existing `client_secret` value is carried over into the new row
- **THEN** a success flash message is displayed

#### Scenario: Save rejected when a required field is empty
- **WHEN** the SA user submits the form with any of `type`, `issuer_url`, or `client_id` left blank, OR with `client_secret` blank on first save
- **THEN** the form is re-displayed with a validation error
- **THEN** the existing config row is unchanged

#### Scenario: Save succeeds without a provider value
- **WHEN** the SA user submits the form with `type`, `issuer_url`, `client_id`, and `client_secret` filled
- **THEN** the config is saved successfully with no `provider` column value

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

### Requirement: Audit metadata display
When an `auth_config` row exists, the page SHALL display a single-line audit trail below the save form showing "Last updated [timestamp] by [name]". The timestamp SHALL be converted to the browser's local timezone without an inline timezone label. A `title` tooltip on the timestamp element SHALL contain the local time, IANA timezone name, and raw UTC value. The `created_at` / `created_by` fields SHALL NOT be shown anywhere on this page. See ADR-0015.

#### Scenario: Audit line shown when config exists
- **WHEN** the SA user loads `/admin/auth_config.php` and an `auth_config` row exists
- **THEN** a line reading "Last updated [local timestamp] by [name]" is present below the form
- **THEN** hovering the timestamp shows the IANA timezone name and raw UTC value in the tooltip

#### Scenario: Audit line absent when no config exists
- **WHEN** the SA user loads `/admin/auth_config.php` and `auth_config` has zero rows
- **THEN** no audit metadata line is present

#### Scenario: Created fields not shown
- **WHEN** the SA user loads `/admin/auth_config.php`
- **THEN** no `created_at` or `created_by` information is rendered anywhere on the page

---

### Requirement: Login page OIDC button state
`/admin/login.php` SHALL always render the homegrown username/password form regardless of `auth_config` state. When `auth_config` has a row with `is_active = 1`, the page SHALL additionally render an SSO provider button below a visual separator. The SSO button SHALL link to `/admin/auth/redirect.php`. When `auth_config.icon_url` is non-NULL, the button SHALL render an `<img>` element with `src` set to `icon_url` to the left of the label text. When `icon_url` is NULL, the button SHALL render label text only with no image placeholder. When no active row exists (`auth_config` has zero rows or `is_active = 0`), no SSO button SHALL be rendered.

#### Scenario: Homegrown form always shown regardless of provider state
- **WHEN** a visitor loads `/admin/login.php` and `auth_config` has an active provider
- **THEN** the username/password form is present in the response

#### Scenario: Homegrown form shown when no provider is configured
- **WHEN** a visitor loads `/admin/login.php` and `auth_config` has zero rows
- **THEN** the username/password form is present in the response
- **THEN** no SSO button is present in the response

#### Scenario: SSO button shown in addition to homegrown form when provider is active
- **WHEN** a visitor loads `/admin/login.php` and an `auth_config` row with `is_active = 1` exists
- **THEN** both the username/password form and the SSO provider button are present in the response

#### Scenario: SSO button links to redirect handler
- **WHEN** a visitor loads `/admin/login.php` and an active provider is configured
- **THEN** the SSO button links to `/admin/auth/redirect.php`

#### Scenario: SSO button renders icon when icon_url is set
- **WHEN** a visitor loads `/admin/login.php` and `auth_config.icon_url` is `https://cdn.example.com/logo.svg`
- **THEN** the SSO button contains an `<img>` element with `src="https://cdn.example.com/logo.svg"` to the left of the label

#### Scenario: SSO button renders label only when icon_url is null
- **WHEN** a visitor loads `/admin/login.php` and `auth_config.icon_url` is NULL
- **THEN** the SSO button contains the label text only, with no `<img>` element

#### Scenario: SSO button hidden when provider is inactive
- **WHEN** a visitor loads `/admin/login.php` and `auth_config.is_active = 0`
- **THEN** no SSO button is present in the response
- **THEN** the username/password form is present in the response
