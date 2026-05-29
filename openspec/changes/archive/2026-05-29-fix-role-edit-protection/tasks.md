## 1. Shared helper

- [x] 1.1 Add `role_rank(string $role): int` to `includes/auth.php` — returns `sa`→3, `admin`→2, `faculty`→1, `user`→0, unknown→-1

## 2. Server-side guards (`admin/users_ajax.php`)

- [x] 2.1 In `guard_target()`: after the sentinel and self-edit checks, add `if (role_rank($target['role']) > role_rank($me['role'])) ajax_err('Permission denied.', 403);`
- [x] 2.2 In the `add_user` case: after role enum validation, add `if (role_rank($role) > role_rank($me['role'])) ajax_err('Permission denied.', 403);`

## 3. UI — row locking (`admin/users.php`)

- [x] 3.1 Update `$locked` computation to `$is_sa || $is_me || (role_rank($u['role']) > role_rank($user['role']))`
- [x] 3.2 In the Add User modal role `<select>`: wrap each `<option>` in a conditional that omits options where `role_rank($rv) > role_rank($user['role'])`
