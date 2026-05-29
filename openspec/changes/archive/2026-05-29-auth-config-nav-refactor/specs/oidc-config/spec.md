## MODIFIED Requirements

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
