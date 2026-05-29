## Why

The current same-rank peer lock is over-broad and contains hardcoded `sa`-role exemptions scattered across the guard logic and UI rendering. Replacing it with a uniform creator-scoped model removes the special-casing, gives every actor (including `sa`) a consistent ownership concept, and makes peer-row locking mean one thing everywhere: "you didn't create this user."

## What Changes

- **BREAKING**: The `sa`-role exemption from same-rank peer locking is removed. Non-sentinel `sa` actors are now subject to creator-scoping for same-rank peers, identical to all other roles.
- The `guard_target()` function's `block_same_rank` boolean parameter is replaced with a creator-ownership check: same-rank targets not created by the acting user are rejected with 403 for all mutations except `toggle_active`.
- `user_delete()` gains a re-assign step: before the `DELETE`, all rows where `created_by = target_id` are updated to `created_by = deleter_id` (re-assign on delete).
- UI row classification: `irm-peer-row` now applies to any same-rank row whose `created_by` differs from the logged-in user's `id`, regardless of whether the actor is `sa`. Same-rank rows the actor created are rendered fully unlocked.
- Cross-rank access (higher-rank actor → lower-rank target) is unchanged: full access, no creator restriction.
- The sentinel (`email = 'admin' AND role = 'sa'`) continues to bypass all checks. No schema change.
- Hardcoded `$me['role'] !== 'sa'` exemptions in the UI and server guard are removed.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `user-management`: The same-rank peer locking requirement changes from a role-exemption model to a creator-scoped model. The `guard_target` server behaviour, the `irm-peer-row` render condition, the `user_delete` DB function, and every `sa`-exemption branch are all in scope for spec changes.

## Impact

- `admin/users.php` — `$is_peer` logic updated; `$is_sa`-exempt branch removed
- `admin/users_ajax.php` — `guard_target()` rewritten; `block_same_rank` param removed
- `includes/db_users.php` — `user_delete()` gains re-assign step
- `openspec/specs/user-management/spec.md` — same-rank peer locking and server guard requirements rewritten
- `CONTEXT.md` — new terms: Sentinel, Creator, Creator-scoped, Peer row, Re-assign on delete
