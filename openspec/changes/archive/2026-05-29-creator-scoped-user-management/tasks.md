## 1. DB Layer — re-assign on delete

- [x] 1.1 In `includes/db_users.php` `user_delete()`, add a PDO UPDATE statement before the DELETE that sets `created_by = audit_by()` for all rows where `created_by = :id`

## 2. Server Guard — creator-ownership check

- [x] 2.1 In `admin/users_ajax.php` `guard_target()`, rename parameter from `bool $block_same_rank = true` to `bool $allow_same_rank_active = false`
- [x] 2.2 Replace the `$block_same_rank && $me['role'] !== 'sa'` same-rank block with: reject if same-rank AND `(int)$target['created_by'] !== (int)$me['id']` AND NOT `$allow_same_rank_active`
- [x] 2.3 Update the `toggle_active` call site from `guard_target($id, block_same_rank: false)` to `guard_target($id, allow_same_rank_active: true)`

## 3. UI Renderer — peer row classification

- [x] 3.1 In `admin/users.php` `$is_peer` assignment, remove the `$user['role'] !== 'sa' &&` exemption and add `&& (int)$u['created_by'] !== (int)$user['id']`

## 4. Verification

- [x] 4.1 As sentinel (`admin`): verify full access to all rows, including non-sentinel `sa`-role rows
- [x] 4.2 As a non-sentinel `sa`: verify rows they created are fully unlocked; same-rank rows they did not create show `irm-peer-row` with active toggle only
- [x] 4.3 As `admin`: verify rows they created show Edit / Reset Password / Delete in the action menu; unowned same-rank rows show active toggle only
- [x] 4.4 As `admin`: verify cross-rank rows (`faculty`, `user`) are still fully editable regardless of `created_by`
- [x] 4.5 Delete a user who created others; verify those users' `created_by` is updated to the deleter and they remain fully manageable by the deleter
