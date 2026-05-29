# ADR-0004: Real OIDC redirect handler with placeholder callback

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: project owner

## Context

Implementing a complete OIDC/SAML flow requires two endpoints: a redirect handler (sends the browser to the provider) and a callback handler (receives the auth code, exchanges it for tokens, validates claims, creates a session). The token exchange involves cryptographic validation (JWT signature, nonce, state verification, PKCE code verifier) and is complex to implement correctly. This refactor cycle focuses on auth config UI, the login page, and navigation — full SSO token exchange is deferred.

## Decision

We implement `admin/auth/redirect.php` as a real, functional handler that constructs a valid OIDC authorization URL using `issuer_url`, `client_id`, `scopes`, and optional PKCE (`code_verifier`/`code_challenge` via `random_bytes()` + `hash('sha256', ...)`), stores `state` and `nonce` in `$_SESSION`, and sends the browser to the provider. The `admin/auth/callback.php` is a placeholder that accepts the provider's redirect and renders "SSO callback not yet implemented."

## Alternatives Considered

### Alternative 1: Full OIDC implementation including token exchange
- **Pros**: Complete end-to-end SSO flow in one cycle
- **Cons**: Significant scope — token endpoint request, JWT validation, claim mapping, session creation, error handling for all OIDC error codes; high risk of subtle security bugs without a library
- **Why not**: Out of scope for this cycle; the auth config UI refactor and navigation work is the priority

### Alternative 2: Button href points directly to issuer_url
- **Pros**: Zero backend code for the SSO path; trivially simple
- **Cons**: Not a real OIDC flow — `issuer_url` is the discovery document URL, not the authorization endpoint; no `state`/`nonce`/`client_id` parameters; offers no security properties
- **Why not**: Functionally incorrect and not extensible; would need to be completely replaced rather than extended

## Consequences

### Positive
- The redirect handler establishes the correct OIDC infrastructure — `state`, `nonce`, and PKCE are wired up, so adding the callback in a future cycle is additive, not a rewrite
- The round-trip to the provider can be validated (browser reaches the provider's login page) even before the callback is complete
- No external library dependency — `random_bytes()` and `hash()` are PHP built-ins

### Negative
- The SSO flow is incomplete — users who click the SSO button reach the provider login page but return to a "not implemented" page; this is expected and documented
- The placeholder callback must be clearly labeled to avoid confusion in staging environments

### Risks
- If `state` or `nonce` session storage is implemented incorrectly here, the future callback implementation will need to be adjusted — mitigated by keeping the session key names explicit and documented in `redirect.php`
