## Why

The Users page protects the sentinel SA row and the logged-in user's own row, but has no role-hierarchy awareness. An `admin`-role user can currently edit, delete, reset passwords for, and reassign roles of any `sa`-role user that is not the hardcoded sentinel — and can even create new `sa` users via the Add User modal. A lower-role actor must never be able to mutate a higher-role target.

## What Changes

- The `$locked` computation in `users.php` changes from a sentinel+self check to a sentinel+self+hierarchy check: any row whose role rank is strictly higher than the acting user's role rank is locked.
- `guard_target()` in `users_ajax.php` gains a hierarchy guard: if the target's role rank exceeds the actor's role rank, the request is rejected with 403.
- The `add_user` action in `users_ajax.php` gains a hierarchy guard: if the requested new role's rank is strictly higher than the actor's role rank, the request is rejected with 403.
- The Add User modal role `<select>` in `users.php` hides any role option that is strictly higher than the acting user's role (UI hint only — server is the authoritative guard).
- Higher-role rows (from the actor's perspective) receive the same `irm-sa-row` visual treatment as the sentinel row: muted background, disabled inline controls, no action menu, read-only role badge.

## Capabilities

### New Capabilities

_(none — this is a targeted bug fix to existing behaviour)_

### Modified Capabilities

- `user-management`: row-locking logic and server-side mutation guards must reflect role hierarchy, not just the sentinel+self pattern.

## Impact

- `admin/users.php` — `$locked` logic and Add User role `<select>` rendering
- `admin/users_ajax.php` — `guard_target()` function and `add_user` case
- `openspec/specs/user-management/spec.md` — delta spec needed for updated row-locking and guard requirements
