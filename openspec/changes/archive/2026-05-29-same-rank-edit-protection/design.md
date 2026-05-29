## Context

After `fix-role-edit-protection`, `admin` users cannot mutate `sa` users (hierarchy: target rank > actor rank → blocked). However, `admin` users can still fully edit, delete, reset passwords for, and change roles of other `admin` peers. The proposal extends protection to same-rank peers, with one deliberate exception: `toggle_active` remains permitted so admins retain basic operational authority over peer accounts.

The existing `guard_target()` in `users_ajax.php` is the single enforcement point for all mutation actions. The `role_rank()` helper in `includes/auth.php` is already in place from ADR-0013.

## Goals / Non-Goals

**Goals:**
- Block `admin` from: update_name, update_role, toggle_sso, reset_password, delete on same-rank peers.
- Allow `admin` to: toggle_active on same-rank peers.
- Enforce both server-side (403) and UI (controls disabled, row styled).
- Keep `sa` actors unaffected — they already pass both the higher-rank and same-rank checks.

**Non-Goals:**
- Not changing the sentinel check (hardcoded `email='admin' AND role='sa'`).
- Not restricting `faculty` or `user` roles on the users page (they cannot access it).
- Not adding same-rank protection to `add_user` — creating a peer is not mutating one.

## Decisions

### Decision 1 — Parameterize `guard_target()` rather than duplicate it

**Chosen:** Add a `bool $block_same_rank = true` parameter to `guard_target()`.

- When `true` (default): same-rank target → 403. All existing call sites are unchanged.
- When `false`: same-rank passes through; only strictly higher-rank is blocked.
- `toggle_active` calls `guard_target($id, block_same_rank: false)`.

**Alternatives considered:**
- **A — Separate `guard_target_peer_ok()` function**: Near-duplicate of `guard_target()`; two functions diverge over time.
- **B — Inline the check per action case**: Scatters identical logic across 6 switch cases; easy to miss one.

Parameterization keeps a single security function as the authoritative guard while the named-argument call site is self-documenting.

> **ADR:** ADR-0014 — guard_target() same-rank parameter strategy. See `docs/adr/0014-guard-target-same-rank-param.md`.

### Decision 2 — UI: `$is_peer` variable + `irm-peer-row` CSS class

**Chosen:** Introduce `$is_peer = (role_rank($u['role']) === role_rank($user['role']) && !$is_sa && !$is_me && !$is_higher)`.

- `irm-peer-row` class: same muted background as `irm-sa-row`, but reduced opacity is scoped to the non-toggle controls (via child selectors), so the active toggle retains full visual weight.
- In the row template: controls that are fully locked use `$locked || $is_peer` as their disabled condition. The active toggle uses only `$locked`.
- `$tr_class` gains a `$is_peer` branch: `irm-peer-row`.

**Alternatives considered:**
- **Reuse `irm-sa-row`**: Simpler (no new class), but the row-wide reduced opacity would make the enabled toggle look disabled — misleading UX.
- **Per-control `$can_*` flags**: Very explicit but verbose; `$is_peer` is a cleaner single-flag equivalent.

### Decision 3 — `add_user` is unchanged

Creating a user with the same role as the actor is not "mutating a peer". The hierarchy check (`role_rank($role) > role_rank($me['role'])`) already allows admins to create other admins, and that intent should remain. No change needed.

## Risks / Trade-offs

- **Default `true` is the safe direction**: Any new action that calls `guard_target()` without arguments will block same-rank by default — safe-by-default.
- **`irm-peer-row` CSS addition**: Small CSS surface area. The class needs to be added to `admin/style.css` to avoid rendering as unstyled rows.
- **Partial-disable UI**: Some controls in a peer row are enabled (toggle_active), others are not. The visual treatment must make this obvious. `irm-peer-row` with selective opacity achieves this but requires careful CSS selector scoping.

## Migration Plan

No DB schema change. No migration needed. Deploy is a PHP + CSS file swap.

Rollback: revert `users_ajax.php`, `users.php`, `admin/style.css`.

## Open Questions

_(none — scope is fully bounded by proposal)_
