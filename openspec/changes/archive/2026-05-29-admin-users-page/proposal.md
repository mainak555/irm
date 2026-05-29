## Why

The admin panel currently has no interface for managing user accounts — only the initial SA setup and the SA's own profile page exist. Administrators need a dedicated page to create, view, update, and remove users, control account activation, assign roles, and reset passwords.

## What Changes

- New page `admin/users.php` accessible to `sa` and `admin` roles
- New AJAX/POST endpoints for inline active-toggle, role-change, and SSO-flag edits
- New `includes/db_users.php` helper module for all `auth_users` CRUD queries
- Password-reset flow that generates a compliant random password and displays it once
- Add-user modal with role defaulting to `user`
- SA (`role='sa'`, `username='admin'`) row is permanently pinned at top, protected from deletion and deactivation

## Capabilities

### New Capabilities

- `user-management`: Full CRUD UI for `auth_users` — list all users in a Material Shadcn table, add a user via modal, inline-toggle active status, inline-change role, inline-toggle SSO flag, edit user name, reset password (non-SSO users only), and delete non-SA users. The SA row is always pinned first and immutable.

### Modified Capabilities

*(none — existing auth/session requirements are unchanged)*

## Impact

- **New files**: `admin/users.php`, `includes/db_users.php`
- **Modified files**: `admin/_layout.php` (sidebar nav link), `sql/schema.sql` (add `sso` column to `auth_users` if not present)
- **DB**: `auth_users` table — may need `sso TINYINT(1) NOT NULL DEFAULT 0` column added
- **Auth**: page calls `require_auth('sa', 'admin')`; delete/deactivate/reset actions additionally guard against targeting the SA row server-side
- **No breaking changes** to existing login, logout, profile, or OIDC flows
