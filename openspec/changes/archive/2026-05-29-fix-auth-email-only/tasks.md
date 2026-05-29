## 1. Schema (`sql/schema.sql`)

- [x] 1.1 Remove the `username VARCHAR(50) NULL UNIQUE` column from the `CREATE TABLE auth_users` definition
- [x] 1.2 Update the comment on the `email` column to note that `'admin'` is the reserved sentinel for the SA account
- [x] 1.3 Add a "Live migration" comment block with the two SQL statements for existing installs (`UPDATE … SET email = 'admin'` then `ALTER TABLE … DROP COLUMN username`)

## 2. DB Login Functions (`includes/db_login.php`)

- [x] 2.1 In `auth_user_create_sa()`: change `INSERT` to use `email = 'admin'` instead of `username = 'admin'`; remove `username` from the column list
- [x] 2.2 Delete the `auth_user_find_by_username()` function entirely

## 3. Login Page (`admin/login.php`)

- [x] 3.1 Change the POST variable read from `$_POST['username']` to `$_POST['email']`
- [x] 3.2 Switch the user lookup call from `auth_user_find_by_username()` to `auth_user_find_by_email()`
- [x] 3.3 Change the form field `name="username"` → `name="email"`, update `autocomplete="email"`, and update the repopulation `value=` to use `$_POST['email']`
- [x] 3.4 Change the field label from "Username" to "Email / Username"

## 4. User Management Backend

- [x] 4.1 In `includes/db_users.php` `users_list()`: change ORDER BY from `(username = 'admin' AND role = 'sa') DESC` to `(email = 'admin' AND role = 'sa') DESC`
- [x] 4.2 In `admin/users_ajax.php` `guard_target()`: change the SA guard check from `$target['username'] === 'admin'` to `$target['email'] === 'admin'`

## 5. Users Page (`admin/users.php`)

- [x] 5.1 Change `$is_sa` detection from `$u['username'] === 'admin'` to `$u['email'] === 'admin'`
- [x] 5.2 In the Email `<td>`, render `—` when `$u['email'] === 'admin'`, otherwise `h($u['email'] ?? '')`

## 6. Verify

- [x] 6.1 Log in as SA by typing `admin` in the Email / Username field — confirm redirect to dashboard
- [x] 6.2 Log in as a non-SSO user using their email address — confirm login succeeds
- [x] 6.3 On the Users page, confirm the SA row is first and the Email column shows `—`
- [x] 6.4 Confirm SA row controls (active toggle, SSO, delete) remain locked/absent
