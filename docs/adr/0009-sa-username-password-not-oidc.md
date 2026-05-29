# ADR-0009: SA super admin uses username+password, not email+OIDC

**Date**: 2026-05-28
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

On first launch, no OIDC/SAML provider has been configured yet. The system needs a way for the initial administrator to log in and configure the OIDC provider before any other user can authenticate. The sa (super admin) is a privileged bootstrap account, not a regular user account. Requiring the sa to authenticate via OIDC creates a chicken-and-egg problem: you cannot configure OIDC before logging in, and you cannot log in before configuring OIDC.

## Decision

The sa account authenticates with a fixed `username = 'admin'` and a bcrypt-hashed password stored in `auth_users.password`. The sa account has no email address. The login form always shows a username+password form. All other roles (admin, faculty, user) authenticate via the configured OIDC/SAML provider and have no password column value.

## Alternatives Considered

### Alternative A: SA uses email+password like other users
- **Pros**: Uniform authentication path; simpler login form (one type of credential for everyone)
- **Cons**: Still creates chicken-and-egg for OIDC setup; requires email for sa which is unnecessary
- **Why not**: Does not solve the bootstrap problem; OIDC users authenticated via email would share the same login form which adds confusion

### Alternative B: SA uses OIDC too
- **Pros**: Fully unified auth; no special-cased code path
- **Cons**: Cannot log in before OIDC is configured; no recovery path if OIDC provider goes down
- **Why not**: Eliminates the ability to bootstrap and recover the system

## Consequences

### Positive
- System is always accessible for administration even if OIDC provider is misconfigured or unavailable
- Clear separation: sa is a system account, everyone else is an identity-provider account
- No circular dependency between auth and OIDC configuration

### Negative
- Two authentication code paths must be maintained (username+password for sa, OIDC redirect for others)
- `auth_users` has mixed nullable columns: email is null for sa, password is null for non-sa

### Risks
- **Risk**: sa password is the single point of failure if forgotten → **Mitigation**: Password reset can be done via a CLI script or by re-running the first-launch setup (after manually clearing auth_users) as a recovery procedure
