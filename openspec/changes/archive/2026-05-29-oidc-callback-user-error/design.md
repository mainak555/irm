## Context

`admin/auth/error.php` was built to handle OIDC provisioning failures — cases where the OIDC token is valid but the authenticated email is not in `auth_users` or has no role assigned. The implementation is verified working. The page currently uses a bare centered `<div>`, which breaks visual continuity for users who just came from `login.php`'s card layout.

See [C4 Component diagram](../../../docs/architecture/c4-components-oidc-auth.md) and [C4 Dynamic — Provisioning Error Flow](../../../docs/architecture/c4-dynamic-oidc-callback.md) for the auth flow context.

## Goals / Non-Goals

**Goals:**
- Restyle `error.php` to use the `.auth-card` + `.card` layout from `login.php`
- Fix card title as "Access Not Granted"
- Preserve all existing PHP logic and session handling unchanged

**Non-Goals:**
- Any changes to `callback.php` session logic or redirect decisions
- Changes to `403.php` (different journey — post-auth, not auth-flow)
- Auto-provisioning or any new database interaction
- Inactive-account (`is_active = 0`) handling — stays on `oidc_fail()` → `login.php` path

## Decisions

### 1. Auth-card layout over bare centered div

`error.php` adopts the `.auth-card` → `.card.shadow-sm` → `.card-body.p-4` pattern from `login.php` instead of the bare `<div class="text-center">` used by `403.php`.

**Rationale**: Users arrive at `error.php` directly from the OIDC flow after clicking the SSO button on `login.php`. Visual continuity requires the same card frame. The 403 page serves a different journey (authenticated user, wrong role) and intentionally uses a different layout.

Recorded in [ADR-0012](../../../docs/adr/0012-oidc-error-page-auth-card-layout.md).

### 2. Fixed card title "Access Not Granted"

The card title is a fixed string, not `cfg('school.title')`. The error page communicates a *state* (access failed), not an *identity* (which school this is). The school name adds no value on an error screen.

### 3. No PHP logic changes

The session handling (`$_SESSION['oidc_provision_error']` read + unset), fallback message, and "Back to Login" link remain exactly as implemented. This is a presentation-only change.

## Risks / Trade-offs

- **Two standalone layout patterns now exist** (card for auth-flow, bare for admin-shell errors) → Future auth-flow pages must follow the card pattern. ADR-0012 captures this convention.
- **`error.php` has no auth guard** → Intentional and unchanged. The user has no IRM session at this point; guarding the page would create a redirect loop.

## Migration Plan

1. Replace the `<div class="text-center">` wrapper in `error.php` with `.auth-card` + `.card.shadow-sm` + `.card-body.p-4`
2. Add `<h4 class="card-title mb-1">Access Not Granted</h4>` as the heading
3. Move the dynamic message and sub-message into the card body
4. Make the "Back to Login" button full-width (`w-100`)
5. No rollback needed — HTML-only change with no PHP or DB impact
