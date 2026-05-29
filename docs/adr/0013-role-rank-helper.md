# ADR-0013: role_rank() helper for role hierarchy comparison

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: Mainak Chowdhury

## Context

The Users page needs to enforce a strict role hierarchy: a lower-role user must
not be able to edit, delete, or create a higher-role user. The four roles have
a defined ordinal rank — sa > admin > faculty > user. This comparison is needed
in at least two places: the `guard_target()` server-side guard and the `add_user`
handler. The UI also needs it to compute `$locked` per row.

## Decision

Add a `role_rank(string $role): int` helper to `includes/auth.php` that returns
an integer ordinal for a given role string (`sa`→3, `admin`→2, `faculty`→1,
`user`→0, unknown→-1). All hierarchy comparisons use this function.

## Alternatives Considered

### Alternative A: PHP constant ROLE_RANK array
- **Pros**: Slightly lower call overhead; array is directly inspectable
- **Cons**: Callers must handle the array lookup and the unknown-role fallback
  themselves; the fallback logic gets duplicated
- **Why not**: Pushes boilerplate to every call site

### Alternative C: Inline comparisons at each call site
- **Pros**: No shared abstraction; each guard is self-contained
- **Cons**: Role ordering is defined in multiple places — a future role addition
  requires updating every guard independently
- **Why not**: Violates single source of truth for the ordering

## Consequences

### Positive
- Single source of truth for role ordering — adding or reordering a role means
  one change in one function
- Unknown roles return -1 (safely editable by everyone), which is a conservative
  fallback consistent with input validation elsewhere
- Readable at call sites: `role_rank($target['role']) > role_rank($me['role'])`

### Negative
- Adds a function call where a direct array lookup would suffice in
  performance-sensitive paths (not a concern here — called once per page render)

### Risks
- An unknown role in the DB would rank -1, making it editable by any known-role
  user. Mitigation: roles are validated to the four-value enum on all write paths,
  so unknown roles cannot enter the DB through normal operation.
