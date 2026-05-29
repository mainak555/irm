## Context

The OIDC callback (`admin/auth/callback.php`) currently uses a single `oidc_fail()` helper for all failure modes: protocol errors (state mismatch, expired token, nonce failure) and provisioning failures (email not found, role missing). Both result in a flash error on `login.php`. For provisioning failures this is a poor UX: the user successfully authenticated with the identity provider but has no account in IRM — a state they cannot resolve by retrying login. The `admin/403.php` page exists as a standalone, sessionless error page pattern that we can follow.

## Goals / Non-Goals

**Goals:**
- Separate provisioning failures from protocol/technical failures at the redirect level.
- Render a clear, helpful error page for provisioning failures with instruction to contact an administrator.
- Keep protocol/technical errors on the existing `oidc_fail()` → `login.php` path (these are transient and worth retrying).

**Non-Goals:**
- Auto-provisioning (creating accounts on first OIDC login) — not in scope.
- Changing the inactive-account (`is_active = 0`) handling — stays as `oidc_fail()` since that state can be changed by an admin without any new concept.
- Any admin UI for managing unprovisioned users.
- Exposing the user's email address in the error page URL or response body.

## Decisions

### 1. Dedicated error page over reusing 403.php or flash-on-login

**Decision:** New file `admin/auth/error.php` patterned after `admin/403.php`.

**Rationale:** A provisioning failure is conceptually different from "access denied" (403) and from a login failure (flash-on-login). The user *did* authenticate — they just aren't in this system yet. A distinct page allows a distinct, accurate message. Reusing 403 would carry the wrong HTTP/conceptual framing.

**Alternative considered:** Flash message on `login.php` with a different error type — rejected because the SSO button is still visible on that page, inviting the user to retry a flow that will fail again with the same result.

### 2. Session key to carry the reason to the error page

**Decision:** Write `$_SESSION['oidc_provision_error']` (a plain string) before redirecting to `error.php`, consume it on `error.php`, display it, then unset it.

**Rationale:** Consistent with the project's existing flash pattern (`$_SESSION['flash']`). Keeps the reason server-side — no user-controlled or URL-visible content. The error page is a one-shot render; if the user refreshes without the session key present, it shows a generic fallback message.

**Alternative considered:** Query-string enum (`error.php?reason=unregistered`) — simpler but slightly leaks internal state classification in URLs; rejected in favour of session parity.

### 3. New `oidc_provision_fail()` helper alongside existing `oidc_fail()`

**Decision:** Add a second never-returning helper `oidc_provision_fail(string $msg): never` in `callback.php` that writes `$_SESSION['oidc_provision_error']` and redirects to `/admin/auth/error.php`.

**Rationale:** Keeps the two failure paths clearly named and symmetric. `oidc_fail()` = technical/transient → login. `oidc_provision_fail()` = access/provisioning → error page. No shared code needed; both are two-liners.

### 4. Role check: `empty($user['role'])`

**Decision:** After `auth_user_find_by_email` returns a non-null row, check `empty($user['role'])`. If true, call `oidc_provision_fail()`.

**Rationale:** `role` is a VARCHAR; an empty string or NULL both mean "not assigned". `empty()` covers both. The `auth_users` schema uses NOT NULL with a default of `''` in some deployments; a strict `=== null` would miss the empty-string case.

## Risks / Trade-offs

- **Session consumed before error.php renders** → If the redirect is intercepted or the user navigates away and returns, `error.php` will show a generic fallback. Acceptable: the page remains helpful ("contact administrator") even without the specific reason.
- **Error page has no auth guard** → Publicly accessible. Intentional: the user has no IRM session yet. The page contains no sensitive data.
- **`oidc_provision_fail()` defined in callback.php only** → Not a shared helper. If a second entry point to OIDC login were ever added, the logic would need to be duplicated or extracted. Acceptable for the current single-callback architecture.

## Migration Plan

1. Add `oidc_provision_fail()` to `admin/auth/callback.php`.
2. Replace the two `oidc_fail()` calls (email-not-found, role-empty) with `oidc_provision_fail()`.
3. Create `admin/auth/error.php` — no auth guard, reads and unsets `$_SESSION['oidc_provision_error']`, renders Bootstrap standalone page matching `admin/403.php` layout.
4. No DB migration, no config change, no dependency update.
5. Rollback: revert the two changed lines in `callback.php` and delete `error.php`.

## Open Questions

- Should the inactive-account case (`is_active = 0`) also be redirected to the error page? Currently left on `oidc_fail()` → login. Could be moved if the UX team decides "account exists but is blocked" warrants the same treatment as "not provisioned".
