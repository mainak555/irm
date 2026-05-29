# ADR-0012: OIDC error page uses auth-card layout for auth-flow visual continuity

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

`admin/auth/error.php` is reached exclusively from within the OIDC sign-in flow. The user clicked the SSO button on `login.php`, authenticated with the identity provider, and was redirected here because their email is not in `auth_users` or their role field is empty. Two standalone page layout patterns already exist in the codebase: `login.php` uses `.auth-card` + `.card` (a 420px-max card with border, shadow, and padding), while `403.php` uses a bare centered `<div>`. The decision was which pattern `error.php` should follow.

## Decision

`error.php` uses the `.auth-card` → `.card.shadow-sm` → `.card-body.p-4` layout pattern from `login.php`, with a fixed card title of "Access Not Granted". The `403.php` bare-div pattern is not used.

## Alternatives Considered

### Alternative 1: Bare centered div (like 403.php)
- **Pros**: Simpler markup; consistent with the other standalone error page
- **Cons**: Creates visual discontinuity — user transitions directly from the card frame of `login.php` to a bare, unstyled page mid-auth-journey
- **Why not**: The user is in the OIDC auth flow context; the card frame provides continuity that a bare div breaks

### Alternative 2: Reuse 403.php with a query-string parameter
- **Pros**: No new file; one fewer template to maintain
- **Cons**: 403 carries "access denied" semantics for an already-authenticated user; on the OIDC error path the user has no IRM session yet
- **Why not**: Semantically incorrect — presenting a 403 frame to an unauthenticated user who failed provisioning conflates two distinct concepts

## Consequences

### Positive
- Visual continuity: users see the same card frame from `login.php` through the OIDC error page
- Semantic distinction: `error.php` (auth-flow, unauthenticated) and `403.php` (admin-shell, authenticated user with wrong role) serve clearly different journeys and now look different intentionally

### Negative
- Two layout patterns now exist for standalone pages: card (auth-flow pages) and bare centered div (post-auth error pages). This distinction must be upheld manually when new pages are added.

### Risks
- **Risk**: Future developers adding auth-flow pages may copy the `403.php` bare pattern → **Mitigation**: this ADR establishes the convention explicitly; auth-flow pages use `.auth-card`, admin-shell error pages use the bare div
