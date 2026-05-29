## Context

The Users page currently computes a `$locked` flag per row using two checks: is the row the
hardcoded SA sentinel (`email='admin' AND role='sa'`), or is it the logged-in user's own row.
`guard_target()` in `users_ajax.php` applies the same two checks server-side before any mutation.
Neither check is aware of role hierarchy — an `admin`-role user can currently edit, delete, and
reset passwords for any other `sa`-role user that is not the sentinel, and can even create new
`sa` users via the Add User modal.

See [ADR-0013](../../docs/adr/0013-role-rank-helper.md) for the role rank modelling decision and
[c4-dynamic-role-hierarchy-enforcement.md](../../docs/architecture/c4-dynamic-role-hierarchy-enforcement.md)
for the request flow diagram.

## Goals / Non-Goals

**Goals:**
- An actor cannot mutate any user whose role rank is strictly higher than their own
- An actor cannot create a user with a role rank strictly higher than their own
- UI reflects the restriction (locked row rendering, hidden Add User options)
- Server enforces the restriction independently of the UI (no trust in client state)

**Non-Goals:**
- Changing the role enum, DB schema, or access control for the page itself
- Restricting same-role edits (admin A can still edit admin B)
- Any changes to faculty/user paths (they are already 403'd from the page)

## Decisions

### 1 — `role_rank(string $role): int` in `includes/auth.php`

A single function returns an integer ordinal: `sa`→3, `admin`→2, `faculty`→1, `user`→0,
unknown→-1. All hierarchy comparisons use `role_rank($target['role']) > role_rank($me['role'])`.

Alternatives: PHP constant array (pushes fallback boilerplate to callers); inline comparisons
(role ordering duplicated at every guard site). See ADR-0013.

### 2 — `guard_target()` extended, not replaced

The existing SA-sentinel and self-edit checks stay. A third check is appended:
if `role_rank($target['role']) > role_rank($me['role'])`, respond 403. This keeps the
sentinel check explicit (it protects the system account regardless of role logic) while
adding the general hierarchy rule on top.

### 3 — `add_user` handler gets its own hierarchy check

`add_user` does not call `guard_target()` (it has no existing target). A dedicated check
is added after role validation: if `role_rank($role) > role_rank($me['role'])`, reject 403.

### 4 — UI `$locked` computed from hierarchy, not hardcoded sentinel alone

`$locked` in `users.php` changes to:
```php
$locked = $is_sa || $is_me || (role_rank($u['role']) > role_rank($user['role']));
```
Higher-role rows render with `irm-sa-row` class (muted bg, disabled controls, no action menu).

### 5 — Add User role `<select>` filters by actor rank (UI hint only)

Role options with rank > actor's rank are omitted from the rendered `<select>`. The server
is the authoritative guard; this is a UX affordance only.

## Risks / Trade-offs

- **Unknown role in DB** → `role_rank()` returns -1, making the row editable by any known-role
  actor. Acceptable: roles are enum-validated on all write paths; unknown roles cannot enter
  the DB through normal operation.
- **SA sentinel check now partially redundant** — the sentinel row (`role='sa'`) will also be
  caught by the hierarchy guard for `admin` actors. The explicit sentinel check is kept because
  it also applies when the actor is `sa` (prevents SA from using this page to modify the system
  account), and because removing it would be a silent behaviour change.
