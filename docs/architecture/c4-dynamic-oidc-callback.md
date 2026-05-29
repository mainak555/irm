# C4 Dynamic Diagram — OIDC Callback: Provisioning Error Path

Shows the numbered request flow when a user successfully authenticates with the OIDC provider but cannot be provisioned into IRM (email not registered, or no role assigned).

```mermaid
C4Dynamic
  title OIDC Callback — Provisioning Error Flow

  Person(user, "Admin User", "Attempting SSO sign-in")
  System_Ext(oidcProvider, "OIDC Provider", "Google / Okta")
  ContainerDb(db, "MySQL", "MySQL 8", "auth_users table")

  Container_Boundary(adminApp, "IRM Admin") {
    Component(login, "login.php", "PHP", "Login page")
    Component(redirect, "redirect.php", "PHP", "OIDC initiator")
    Component(callback, "callback.php", "PHP", "OIDC callback handler")
    Component(error, "error.php", "PHP", "Provisioning error page")
  }

  Rel(user, login, "1. Clicks SSO button", "HTTPS GET")
  Rel(login, redirect, "2. Redirect", "HTTP 302")
  Rel(redirect, oidcProvider, "3. Authorization redirect (state, nonce, PKCE)", "HTTP 302")
  Rel(oidcProvider, user, "4. Login prompt + consent")
  Rel(oidcProvider, callback, "5. Auth code + state", "HTTP 302 to redirect_uri")
  Rel(callback, oidcProvider, "6. Token exchange (code + code_verifier)", "HTTPS POST")
  Rel(oidcProvider, callback, "7. id_token (JWT)")
  Rel(callback, db, "8. auth_user_find_by_email(email)", "PDO/SQL")
  Rel(db, callback, "9. NULL or row with empty role")
  Rel(callback, error, "10. Set session key + redirect", "HTTP 302")
  Rel(error, user, "11. Render 'Access Not Granted' card")

  UpdateRelStyle(user, login, $offsetY="-10")
  UpdateRelStyle(callback, error, $textColor="red", $lineColor="red")
```

## Flow Decision Points

| Step | Outcome | Path |
|------|---------|------|
| Step 5 | `$_GET['error']` present | → `login.php` via `oidc_fail()` (flash error) |
| Step 5 | State mismatch | → `login.php` via `oidc_fail()` |
| Step 7 | Token expired / nonce mismatch | → `login.php` via `oidc_fail()` |
| Step 9 | User not found (`NULL`) | → `error.php` via `oidc_provision_fail()` |
| Step 9 | User found, `role` empty | → `error.php` via `oidc_provision_fail()` |
| Step 9 | User found, `is_active = 0` | → `login.php` via `oidc_fail()` |
| Step 9 | User found, role set, active | → `index.php` (success) |
