## 1. Database & Schema

- [x] 1.1 Add `sso TINYINT(1) NOT NULL DEFAULT 0` column to `auth_users` in `sql/schema.sql`
- [x] 1.2 Run `ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS sso TINYINT(1) NOT NULL DEFAULT 0;` on any existing install before deploying

## 2. DB Helper Module

- [x] 2.1 Create `includes/db_users.php` with `declare(strict_types=1)` and `require_once` of `db.php`
- [x] 2.2 Implement `users_list(): array` — all rows from `auth_users`, SA row first (`ORDER BY (role='sa') DESC, name ASC`)
- [x] 2.3 Implement `user_get(int $id): array|false`
- [x] 2.4 Implement `user_create(array $data): int` — insert new row, return inserted `id`
- [x] 2.5 Implement `user_update_name(int $id, string $name): void`
- [x] 2.6 Implement `user_update_role(int $id, string $role): void`
- [x] 2.7 Implement `user_toggle_active(int $id): void` — flip `is_active` using `1 - is_active`
- [x] 2.8 Implement `user_toggle_sso(int $id): void` — flip `sso` using `1 - sso`
- [x] 2.9 Implement `user_update_password(int $id, string $hash): void`
- [x] 2.10 Implement `user_delete(int $id): void`
- [x] 2.11 Implement `user_generate_password(): string` — use `random_int()` to build a 10-char password guaranteed to contain uppercase, digit, and special character; shuffle result with Fisher-Yates using `random_int()` (not `str_shuffle()` which uses `mt_rand()`)

## 3. AJAX Endpoint

- [x] 3.1 Create `admin/users_ajax.php`: validate session via `require_auth('sa', 'admin')`, validate CSRF via `hash_equals()`, set `Content-Type: application/json`, route on `$_POST['action']`
- [x] 3.2 Action `toggle_active`: guard SA row, call `user_toggle_active()`, return `{"ok":true}`
- [x] 3.3 Action `update_role`: validate role against allowed enum `['sa','admin','faculty','user']`, guard SA row, call `user_update_role()`, return `{"ok":true}`
- [x] 3.4 Action `toggle_sso`: guard SA row, call `user_toggle_sso()`, return `{"ok":true}`
- [x] 3.5 Action `reset_password`: guard SA row, verify target user `sso=0`, call `user_generate_password()`, bcrypt-hash it, call `user_update_password()`, return `{"ok":true,"password":"<plaintext>"}`
- [x] 3.6 Action `delete`: guard SA row, guard self-delete (`$_SESSION['auth']['id']`), call `user_delete()`, return `{"ok":true}`
- [x] 3.7 Action `update_name`: validate non-empty name, call `user_update_name()`, return `{"ok":true}`

## 4. Users Page — Server Side

- [x] 4.1 Create `admin/users.php` with `declare(strict_types=1)`, standard layout includes, `require_auth('sa', 'admin')`
- [x] 4.2 Handle `action=add` POST: CSRF check, validate Name/Email/Password (if non-SSO, check `PWD_REGEX`)/Role, check email uniqueness, `password_hash()`, call `user_create()`, redirect with flash
- [x] 4.3 Handle `action=edit_name` POST: CSRF check, validate non-empty name, call `user_update_name()`, redirect with flash

## 5. Users Page — Table UI

- [x] 5.1 Render Material Shadcn-styled table with columns: #, Active, Name, Email, Role, SSO, Actions
- [x] 5.2 Apply `style="background: var(--irm-muted);"` (or a CSS class) to the SA row; mark it `data-sa="1"`
- [x] 5.3 Render Serial column as sequential counter (1-based, SA always #1)
- [x] 5.4 Render Active column: `<input type="checkbox" class="form-check-input js-toggle-active">` with `checked` state; disable on SA row
- [x] 5.5 Render Role column: `<select class="form-select form-select-sm js-role-select">` pre-selected to current role; read-only `<span>` on SA row
- [x] 5.6 Render SSO column: `<input type="checkbox" class="form-check-input js-toggle-sso">`; disable on SA row
- [x] 5.7 Render Actions column: Bootstrap dropdown button (`⋮`) per row; omit Edit/Reset/Delete controls for SA row; omit Reset Password item for SSO users

## 6. Users Page — Add User Modal

- [x] 6.1 Add Bootstrap modal (`#addUserModal`) triggered by "Add User" button in page header
- [x] 6.2 Modal form fields: Name (required), Email (required), Password (required unless SSO checked), Role dropdown (default `user`), SSO checkbox
- [x] 6.3 JS: toggle Password field required/disabled when SSO checkbox changes
- [x] 6.4 Modal form posts to `users.php?action=add`; flash message confirms success on reload

## 7. Users Page — JavaScript

- [x] 7.1 Embed CSRF token as `const CSRF = "<?= h($_SESSION['csrf']) ?>"` in a `<script>` block
- [x] 7.2 Wire `.js-toggle-active` checkbox `change` → `fetch` POST to `users_ajax.php` with `action=toggle_active&id=&csrf=`; revert checkbox on error
- [x] 7.3 Wire `.js-role-select` `change` → `fetch` POST with `action=update_role&role=&id=&csrf=`; revert select on error
- [x] 7.4 Wire `.js-toggle-sso` `change` → `fetch` POST with `action=toggle_sso&id=&csrf=`; revert checkbox on error
- [x] 7.5 Wire "Edit Name" menu item → small inline form or `prompt()` → `fetch` POST with `action=update_name`; update cell text on success
- [x] 7.6 Wire "Reset Password" menu item → `fetch` POST with `action=reset_password`; on success render dismissible `alert-success` above table with plaintext in `<code>` and a "Copy" button (`navigator.clipboard.writeText()`); dismiss removes from DOM
- [x] 7.7 Wire "Delete" menu item → `confirm()` dialog → `fetch` POST with `action=delete`; on success remove the table row from DOM

## 8. Sidebar Navigation

- [x] 8.1 Add "Users" sidebar link in `admin/_layout.php` pointing to `users.php`, rendered only when `$user['role']` is `sa` or `admin`; apply active highlight when current page is `users.php`

## 9. Visual Polish & Dark Mode

- [x] 9.1 Verify SA row muted background renders correctly in both light and dark modes
- [x] 9.2 Verify role dropdown, SSO checkbox, and active toggle inherit `--irm-primary` / `--irm-border` tokens
- [x] 9.3 Verify 3-dot dropdown menu uses `--irm-card` background and `--irm-border` borders
- [x] 9.4 Verify Add User modal renders correctly in both themes
- [x] 9.5 Verify password reveal alert is legible and dismissible in both themes
