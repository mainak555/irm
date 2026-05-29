# ADR-0016: Creator-ownership replaces role-based same-rank exemption

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: Mainak Chowdhury

## Context

ADR-0014 introduced a `bool $block_same_rank` parameter to `guard_target()` with a `sa`-role exemption: `sa` actors could mutate any same-rank peer, while `admin` and lower could only toggle-active on peers. This created two problems: the exemption was scattered across the UI (`$me['role'] !== 'sa'` checks in `users.php`) and the guard, and it did not match product intent — even non-sentinel `sa` accounts should have scoped ownership, not god-mode over all peers.

## Decision

Replace the `block_same_rank` boolean with a creator-ownership check: same-rank mutations are blocked unless the acting user is the `created_by` of the target. This applies uniformly at all role levels including `sa`. The sentinel (`email = 'admin' AND role = 'sa'`) retains full bypass. The `block_same_rank` parameter is removed.

## Alternatives Considered

### Alternative A — Keep the sa-role exemption
- **Pros**: No breaking change; non-sentinel `sa` retain full peer access.
- **Cons**: Special-case code in both UI and guard; semantics diverge by role — same concept, two implementations.
- **Why not**: Does not match intent; hardcoded role checks create ongoing inconsistency as new roles or rules are added.

### Alternative B — Add an is_owner boolean column
- **Pros**: Ownership is an explicit DB field independent of creation history.
- **Cons**: `created_by` already encodes ownership; a second column must be kept in sync; requires a schema migration.
- **Why not**: Redundant with existing data; unnecessary migration cost.

### Alternative C — Add an is_sentinel flag column
- **Pros**: Sentinel identity decoupled from email value.
- **Cons**: `email = 'admin' AND role = 'sa'` is already the documented sentinel contract (spec + schema comment); no practical benefit.
- **Why not**: Schema change with no functional gain.

## Consequences

### Positive
- Guard logic is uniform: one code path for all roles, no role-name string checks.
- `users.php` row classification is one expression: `same_rank AND created_by != actor.id`.
- The `sa`-role exemption is fully removed — no more `$me['role'] !== 'sa'` guards in the mutation path.

### Negative
- **Breaking**: Non-sentinel `sa` actors who previously managed other `sa` users they did not create will lose that access.
- Re-assign-on-delete must now be added to `user_delete()` so deleted users do not leave unmanageable orphans.

### Risks
- Existing multi-SA installations where SA users shared management of each other's created users must use the sentinel (`admin`) account for cross-ownership operations after upgrade. → Mitigation: Document in release notes; sentinel is always available as the admin-of-last-resort.
