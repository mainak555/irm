## Why

Non-SSO users added via the admin panel cannot log in because the login form looks up by `username`, but only the SA account (`admin`) has a `username` value — all other users have `username = NULL`. Dropping the `username` column and routing all authentication through the `email` column eliminates this split identity and closes the login gap.

## What Changes

- **BREAKING** Drop `auth_users.username` column. The SA account's identifier moves from `username = 'admin'` to `email = 'admin'` (a reserved non-email sentinel value).
- Login form field label changes from "Username" to "Email / Username" to signal that admin enters `admin` and all other users enter their email address.
- Login lookup switches from `auth_user_find_by_username()` → `auth_user_find_by_email()` (already exists), covering both the SA sentinel and real email addresses.
- `auth_user_create_sa()` in `db_login.php` inserts `email = 'admin'` instead of `username = 'admin'`.
- Every SA-guard check in PHP (`$u['username'] === 'admin'`) and in SQL (`ORDER BY username = 'admin'`) changes to check `email = 'admin' AND role = 'sa'`.
- Schema comment updated: `email` column note changes to reflect the `'admin'` sentinel for the SA account.

## Capabilities

### New Capabilities

_(none — this is a bug fix and schema simplification)_

### Modified Capabilities

- `admin-auth`: Login requirement changes from username-based lookup to email-based lookup; first-launch setup now inserts `email = 'admin'` not `username = 'admin'`.
- `user-management`: SA row detection changes from `username = 'admin'` to `email = 'admin' AND role = 'sa'`; the Email column for the SA row displays `—` (no real email).

## Impact

| File | Change |
|---|---|
| `sql/schema.sql` | Drop `username` column; update comment on `email` |
| `includes/db_login.php` | `auth_user_create_sa()` uses `email`; remove `auth_user_find_by_username()`; login uses `auth_user_find_by_email()` |
| `admin/login.php` | Field label → "Email / Username"; POST reads `$_POST['email']`; lookup via `auth_user_find_by_email()` |
| `includes/db_users.php` | `users_list()` ORDER BY changes to `(email = 'admin' AND role = 'sa') DESC` |
| `admin/users.php` | `$is_sa` detection changes to `$u['email'] === 'admin'`; Email column renders `—` for SA row |
| `admin/users_ajax.php` | `guard_target()` checks `$target['email'] === 'admin'` instead of `username` |
