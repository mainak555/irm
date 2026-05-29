## MODIFIED Requirements

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
