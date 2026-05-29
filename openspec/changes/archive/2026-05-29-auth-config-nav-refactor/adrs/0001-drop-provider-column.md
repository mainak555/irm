# ADR-0001: Drop auth_config.provider column

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: project owner

## Context

`auth_config` had a `provider ENUM('google','okta')` column that constrained the supported identity providers to a hard-coded list. As part of this refactor, the Provider dropdown is removed from the UI — the admin now configures the provider purely through the Type (OIDC/SAML), issuer URL, and client credentials. With no UI collecting the `provider` value, the column has no writer and no reader.

## Decision

We drop the `provider` column entirely from `auth_config`. Provider identity is sufficiently described by `type` + `issuer_url`. `schema.sql` is updated to reflect the new structure; existing installs require a manual `ALTER TABLE auth_config DROP COLUMN provider`.

## Alternatives Considered

### Alternative 1: Keep as free-text VARCHAR
- **Pros**: Flexible, no data loss, could be used for display purposes in the future
- **Cons**: No writer — the field would always be NULL or stale
- **Why not**: A nullable free-text column with no writer is misleading noise in the schema

### Alternative 2: Keep ENUM with hard-coded default
- **Pros**: No schema change required
- **Cons**: Column stores a value that no longer reflects user intent; the ENUM prevents future providers
- **Why not**: Dead data with a misleading constraint is worse than no column at all

## Consequences

### Positive
- Schema is leaner and self-documenting — every column has an active writer
- Removes the implicit constraint that only `google` and `okta` are valid providers

### Negative
- Breaking schema change — any existing `auth_config` rows lose the `provider` value on migration
- Manual `ALTER TABLE` required on existing installs (no migration runner exists in this project)

### Risks
- If a future feature needs to label the provider for display, a new free-text column will need to be added — mitigated by the existing `label` field which already serves this purpose
