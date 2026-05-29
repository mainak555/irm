# ADR-0002: Show both login forms simultaneously when auth config is active

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: project owner

## Context

The login page must support two authentication paths: the homegrown username/password form (SA account) and an external OIDC/SAML provider SSO button. When a provider is configured and active, we need to decide whether to show one or both options. The SA account is the only recovery mechanism if the external provider is misconfigured or unavailable.

## Decision

When `auth_config.is_active = 1`, the login page shows **both** the homegrown username/password form and the provider SSO button. The homegrown form is never hidden, regardless of provider state.

## Alternatives Considered

### Alternative 1: Hide homegrown form when provider is active, add escape-hatch URL
- **Pros**: Cleaner UX — users see only one login method; escape hatch (`?local=1`) allows SA emergency access
- **Cons**: Obscures the SA login path; the escape hatch is a hidden feature that must be documented and remembered; a URL parameter is easy to miss or forget under pressure
- **Why not**: Lock-out risk outweighs UX cleanliness. If the provider goes down or is misconfigured, the SA needs immediate, obvious access to the system.

### Alternative 2: Provider button only, no fallback
- **Pros**: Cleanest UX; forces all users through the provider
- **Cons**: Complete lock-out if provider is unavailable; no recovery path
- **Why not**: Unacceptable availability risk for a single-SA system

## Consequences

### Positive
- The SA always has a visible, direct path to log in regardless of provider state
- Eliminates the lock-out scenario where a misconfigured provider leaves the system inaccessible
- No hidden escape-hatch URLs to document or forget

### Negative
- Login page is slightly busier when a provider is active — two distinct login areas shown simultaneously
- Users may be confused about which path to use (mitigated by clear visual separation and labels)

### Risks
- If the homegrown form is ever removed or broken independently of the provider, there is no fallback — mitigated by the SA password change flow on the profile page
