# ADR-0008: auth_config as a singleton — one OIDC/SAML provider at a time

**Date**: 2026-05-28
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

The system needs to store OIDC/SAML provider configuration (issuer URL, client ID, client secret, PKCE flag, scopes, etc.) so the login page can display SSO buttons and the auth flow can be initiated. The school operates with a single identity provider. Supporting multiple simultaneous providers adds UI complexity (list CRUD vs. a single settings form) and flow complexity (provider selection on login page) with no immediate benefit.

## Decision

The `auth_config` table is treated as a singleton — maximum one row. The admin UI is a single settings form with a provider dropdown (google, okta), not a CRUD list. Operations are: Update (save/overwrite the single record), Clear (delete the record), and Toggle active/inactive. Application code enforces the one-row constraint.

## Alternatives Considered

### Alternative: Multi-row table with sort_order for multiple active providers
- **Pros**: Supports future multi-provider scenarios without schema changes; more flexible
- **Cons**: Requires list CRUD UI, more complex login page (multiple SSO buttons with ordering logic), more complex application code
- **Why not**: YAGNI — the school uses one SSO provider. Complexity should be introduced when the requirement exists, not speculatively.

## Consequences

### Positive
- Simpler admin UI: a single form instead of a list with add/edit/delete
- Simpler login page logic: either show one SSO button or none
- Simpler application code: no iteration or ordering logic needed

### Negative
- Adding a second provider later requires a schema change (remove singleton constraint) and UI rework

### Risks
- **Risk**: Application must enforce the one-row constraint in code since SQL has no native "max one row" constraint → **Mitigation**: `auth_config_save()` uses `TRUNCATE` + `INSERT` (or `DELETE` + `INSERT`) as an atomic pair so only one row ever exists
