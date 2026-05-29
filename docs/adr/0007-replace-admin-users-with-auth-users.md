# ADR-0007: Replace admin_users table with auth_users

**Date**: 2026-05-28
**Status**: accepted
**Deciders**: Mainak (project owner)

## Context

The existing `admin_users` table was designed for a single-tier admin with username+password authentication only. It has no role hierarchy beyond a basic role field, no support for OIDC/SAML identifiers (email), and no concept of nullable passwords for externally-authenticated users. The new admin system requires four distinct roles (sa, admin, faculty, user), email as the primary identifier for OIDC users, and a password column that is only populated for the super admin bootstrap account.

## Decision

Drop `admin_users` entirely and create a new `auth_users` table with: `id`, `username` (nullable, only `'admin'` for sa), `email` (nullable unique, required for non-sa users), `name`, `role` ENUM(sa, admin, faculty, user), `password` (nullable bcrypt, sa only), `is_active`, `theme` ENUM(light, dark, system), `created_at`, `updated_at`.

## Alternatives Considered

### Alternative A: Rename and migrate admin_users
- **Pros**: Preserves existing data and migration history
- **Cons**: Results in a hybrid schema with legacy columns; half-migrated state is confusing
- **Why not**: All existing admin pages are being deleted anyway; there is no data worth preserving

### Alternative C: Keep both tables in parallel
- **Pros**: Zero disruption to existing code during transition
- **Cons**: Two sources of truth for user identity; all new code must decide which table to consult
- **Why not**: Creates indefinite technical debt and a confusing dual-auth system

## Consequences

### Positive
- Clean schema that accurately reflects the new role model and auth strategy
- Nullable `password` column cleanly encodes "this user authenticates externally"
- Single source of truth for all user identity across home-grown and OIDC flows

### Negative
- Any existing admin session data becomes invalid on deploy (one-time disruption)
- The seed `admin/admin123` credential is lost; first-launch setup must be re-run

### Risks
- **Risk**: Existing `sql/` files reference `admin_users` → **Mitigation**: All prior SQL files are replaced by a single `sql/schema.sql` ground truth (see ADR-0008 context)
