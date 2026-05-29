## Why

An `admin`-role user can currently edit, delete, reset the password of, and change the role/SSO settings of other `admin`-role users. A peer-rank user should not have destructive authority over an equal — only a higher-ranked actor (`sa`) should be able to mutate a same-rank account. Toggling active/inactive is a lower-stakes action and is intentionally left available to admins for operational purposes.

## What Changes

- `admin` cannot perform the following on another `admin`-role user: update name, update role, toggle SSO, reset password, delete.
- `admin` **can** still toggle active/inactive on another `admin`-role user.
- `sa` users are unaffected (they can already do everything to any user except the hardcoded sentinel and themselves).
- UI: same-rank peer rows seen by an `admin` actor get inline controls disabled (edit name, role select, SSO toggle, reset password, delete) but the active toggle remains enabled.
- Server: `users_ajax.php` enforces the same rule — 403 on blocked actions, pass-through on `toggle_active`.

## Capabilities

### New Capabilities

_(none)_

### Modified Capabilities

- `user-management`: New requirement — same-rank peer protection. Admins cannot mutate same-rank peers except for toggle_active.

## Impact

- `admin/users_ajax.php` — `guard_target()` gains a same-rank check that short-circuits only non-toggle_active actions; `toggle_active` case bypasses the rank-equality block.
- `admin/users.php` — `$locked` and `$tr_class` logic gains a `$is_peer` branch; disabled controls rendered selectively (active toggle stays live).
- `includes/auth.php` — no change needed; `role_rank()` already exists.
