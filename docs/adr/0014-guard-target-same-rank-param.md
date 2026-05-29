# ADR-0014: guard_target() same-rank enforcement via boolean parameter

**Date**: 2026-05-29
**Status**: superseded by ADR-0016
**Deciders**: Mainak Chowdhury

## Context

The `same-rank-edit-protection` change requires blocking most mutation actions on a same-rank peer while explicitly allowing `toggle_active`. The existing `guard_target(int $id)` function is the single enforcement point for all AJAX mutations. It already enforces the sentinel check, self-edit block, and higher-rank block (ADR-0013). A new same-rank block needed to be wired in, but `toggle_active` must bypass it.

## Decision

Add a `bool $block_same_rank = true` parameter to `guard_target()`. When `true` (the default), a 403 is returned if the target's role rank equals the actor's. `toggle_active` calls `guard_target($id, block_same_rank: false)`. All other call sites are unchanged and inherit the safe default.

## Alternatives Considered

### Alternative A — Separate `guard_target_peer_ok()` function
- **Pros**: No change to existing call sites; new function name is explicit.
- **Cons**: Near-duplicate of `guard_target()`; two functions diverge over time; every new action must decide which to call.
- **Why not**: DRY violation and ongoing maintenance surface.

### Alternative B — Inline check per action case
- **Pros**: No function signature change; each case is self-contained.
- **Cons**: Same-rank logic scattered across 6+ switch cases; easy to miss when adding a new action.
- **Why not**: Scattered security logic is error-prone.

## Consequences

### Positive
- Single security function remains the authoritative enforcement point.
- Named-argument call site (`block_same_rank: false`) is self-documenting.
- Default `true` is the safe direction — any new action added later inherits the block.

### Negative
- The boolean parameter implies that callers must know the distinction; a future author might pass `false` unintentionally.

### Risks
- None significant; the parameter only relaxes one check, and only when explicitly opted out.
