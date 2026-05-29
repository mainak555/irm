## Purpose

Handles the initiation of the OIDC authorization code flow. When a visitor clicks the SSO button on the login page, this handler reads the active provider configuration, generates security tokens (state, nonce, PKCE), and redirects the browser to the identity provider's authorization endpoint.

## Requirements

### Requirement: OIDC authorization redirect
`/admin/auth/redirect.php` SHALL read the single `auth_config` row with `is_active = 1`. When no such row exists, it SHALL set a flash error and redirect to `/admin/login.php`. When an active row exists, it SHALL generate a cryptographically random `state` (16 bytes, hex-encoded) and `nonce` (16 bytes, hex-encoded), store both under `$_SESSION['oidc']`, construct an authorization URL from `issuer_url` + `/authorize` with query parameters `response_type=code`, `client_id`, `redirect_uri`, `scope`, `state`, and `nonce`, and redirect the browser to that URL with HTTP 302.

#### Scenario: Redirect attempted when no active provider is configured
- **WHEN** a visitor navigates to `/admin/auth/redirect.php` and `auth_config` has zero rows or `is_active = 0`
- **THEN** `$_SESSION['flash']` contains an error message indicating no provider is configured
- **THEN** the browser is redirected to `/admin/login.php`

#### Scenario: Redirect stores state and nonce in session
- **WHEN** a visitor navigates to `/admin/auth/redirect.php` and an active `auth_config` row exists
- **THEN** `$_SESSION['oidc']['state']` is set to a 32-character hexadecimal string
- **THEN** `$_SESSION['oidc']['nonce']` is set to a 32-character hexadecimal string

#### Scenario: Authorization URL contains all required parameters
- **WHEN** a visitor navigates to `/admin/auth/redirect.php` and an active `auth_config` row exists
- **THEN** the browser is redirected to a URL whose base is `issuer_url` + `/authorize`
- **THEN** the redirect URL contains `response_type=code`, `client_id`, `scope`, `state`, and `nonce` as query parameters

#### Scenario: State in URL matches state stored in session
- **WHEN** the redirect to the authorization URL is issued
- **THEN** the `state` query parameter in the URL equals `$_SESSION['oidc']['state']`

---

### Requirement: PKCE (always on)
The redirect handler SHALL always generate PKCE — there is no per-config toggle. It SHALL generate a `code_verifier` (32 cryptographically random bytes, base64url-encoded without padding), derive `code_challenge` as `base64url(SHA-256(code_verifier))`, store `code_verifier` in `$_SESSION['oidc']['code_verifier']`, and append `code_challenge_method=S256` and `code_challenge` to the authorization URL.

#### Scenario: PKCE code verifier stored in session on every redirect
- **WHEN** a visitor navigates to `/admin/auth/redirect.php` and an active `auth_config` row exists
- **THEN** `$_SESSION['oidc']['code_verifier']` is set to a non-empty base64url-encoded string

#### Scenario: S256 challenge always appended to authorization URL
- **WHEN** a visitor navigates to `/admin/auth/redirect.php` and an active `auth_config` row exists
- **THEN** the authorization URL contains `code_challenge_method=S256`
- **THEN** the authorization URL contains a `code_challenge` parameter whose value is the base64url-encoded SHA-256 hash of `code_verifier`
