# C4 Component Diagram — OIDC Authentication

Shows the PHP components involved in the OIDC sign-in flow and their relationships.

```mermaid
C4Component
  title Component Diagram — IRM Admin OIDC Authentication

  Person(user, "Admin User", "School staff signing in via SSO")
  System_Ext(oidcProvider, "OIDC Provider", "Google, Okta, or similar identity provider")
  ContainerDb(db, "MySQL", "MySQL 8", "auth_users and auth_config tables")

  Container_Boundary(adminApp, "IRM Admin PHP Application") {
    Component(login, "login.php", "PHP", "Login form with username/password and optional SSO button")
    Component(redirect, "redirect.php", "PHP", "Fetches OIDC discovery, generates state/nonce/PKCE, redirects to provider")
    Component(callback, "callback.php", "PHP", "Exchanges auth code for tokens, validates id_token, looks up user")
    Component(error, "error.php", "PHP", "Provisioning error page — shown when OIDC succeeds but user is not in auth_users or has no role")
    Component(dashboard, "index.php", "PHP", "Admin dashboard — destination after successful sign-in")
    Component(authConfigFn, "auth_config_get()", "PHP/MySQL", "Reads active provider config from auth_config table")
    Component(authUserFn, "auth_user_find_by_email()", "PHP/MySQL", "Looks up auth_users row by email address")
  }

  Rel(user, login, "Visits", "HTTPS")
  Rel(login, redirect, "Redirects on SSO click", "HTTP 302")
  Rel(redirect, authConfigFn, "Reads active provider")
  Rel(redirect, oidcProvider, "Fetches discovery doc + redirects browser", "HTTPS")
  Rel(oidcProvider, callback, "Returns auth code", "HTTP 302 + query params")
  Rel(callback, oidcProvider, "Token exchange (code + PKCE verifier)", "HTTPS POST")
  Rel(callback, authConfigFn, "Reads active provider config")
  Rel(callback, authUserFn, "Looks up user by email")
  Rel(authConfigFn, db, "Queries", "PDO/SQL")
  Rel(authUserFn, db, "Queries", "PDO/SQL")
  Rel(callback, error, "Redirects on provisioning failure", "HTTP 302 + session key")
  Rel(callback, dashboard, "Redirects on success", "HTTP 302")
  Rel(callback, login, "Redirects on protocol error", "HTTP 302 + flash")

  UpdateLayoutConfig($c4ShapeInRow="3", $c4BoundaryInRow="1")
```

## Notes

- **Provisioning failure** = OIDC token is valid but the email is not in `auth_users`, or the user row has no `role`. Redirects to `error.php`.
- **Protocol error** = state mismatch, expired id_token, nonce failure, token endpoint unreachable. Redirects back to `login.php` via flash message.
- `redirect.php` uses dynamic PKCE — only attaches `code_challenge` when the provider's discovery document advertises `S256` in `code_challenge_methods_supported`.
