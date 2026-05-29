## Why

The OIDC provisioning error page (`admin/auth/error.php`) was built and verified working, but its layout uses a bare centered `<div>` rather than the `.auth-card` + `.card` frame used by `admin/login.php`. Users arrive at this page directly from the OIDC sign-in flow, so the visual discontinuity — card login page → bare error page — is jarring and inconsistent with the rest of the auth journey.

## What Changes

- Restyle `admin/auth/error.php` to use the `.auth-card` → `.card.shadow-sm` → `.card-body.p-4` wrapper pattern from `login.php`
- Use `"Access Not Granted"` as the fixed `card-title`
- Retain the dynamic session message (`$_SESSION['oidc_provision_error']`) as the body text beneath the title
- Retain the fixed sub-message "Please contact an administrator to request access."
- Retain the full-width `btn-primary` "Back to Login" link

No functional or session-handling changes. No PHP logic changes. Presentation only.

## Capabilities

### New Capabilities

_(none)_

### Modified Capabilities

- `oidc-callback-user-error`: Add a presentation requirement — the error page SHALL use the auth card layout (`.auth-card` + `.card` + `.card-body`) consistent with `login.php`, with a fixed card title of "Access Not Granted".

## Impact

- `admin/auth/error.php` — HTML structure only; PHP logic unchanged
- `openspec/specs/oidc-callback-user-error/spec.md` — delta spec adds the presentation requirement
