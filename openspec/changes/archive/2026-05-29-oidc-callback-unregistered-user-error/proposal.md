## Why

When a user completes OIDC authentication with a valid identity provider token but their email is not registered in `auth_users`, or they have no role assigned, the current callback silently bounces them back to the login page with a generic flash error. This gives no useful guidance and implies a login failure rather than an access-provisioning issue — the user cannot self-serve and may not understand they need administrator action.

## What Changes

- Add a dedicated OIDC error page (`admin/auth/error.php`) that renders a clear, friendly message explaining the user is authenticated but not yet provisioned, with instruction to contact an administrator.
- Modify `admin/auth/callback.php` to redirect to the error page (instead of `login.php` via flash) for the two authorization-failure cases:
  - Email not found in `auth_users`
  - User row exists but `role` is empty/null (not yet assigned)
- The existing `oidc_fail()` path (for protocol/token errors) continues to redirect to `login.php` as before; the change only affects the user-provisioning failure cases.
- No DB schema changes. No new tables.

## Capabilities

### New Capabilities
- `oidc-callback-user-error`: Dedicated error page and redirect logic for OIDC users who authenticate successfully with the provider but are not provisioned (email not in `auth_users`) or have no role assigned. Covers the error page UI, the redirect decision in the callback, and the distinction from protocol-level errors.

### Modified Capabilities
- `auth-provider-redirect`: The SSO callback placeholder requirement (`callback.php` shows "not yet implemented") is superseded by the real callback implementation. The unregistered-user and missing-role error scenarios need to be captured in spec.

## Impact

- `admin/auth/callback.php` — two new redirect branches replacing `oidc_fail()` calls
- `admin/auth/error.php` — new file (no auth guard; displays static error message)
- `openspec/specs/auth-provider-redirect/spec.md` — placeholder callback requirement removed or superseded
- No changes to `auth_users` schema, `includes/auth.php`, or any other include
