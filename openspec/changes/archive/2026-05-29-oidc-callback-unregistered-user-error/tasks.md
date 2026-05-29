## 1. Error Page

- [x] 1.1 Create `admin/auth/error.php` with `declare(strict_types=1)`, Bootstrap 5.3 standalone layout matching `admin/403.php` (OS-preference theme script, `grain-texture auth-page-bg`)
- [x] 1.2 Read `$_SESSION['oidc_provision_error']` on page load; unset it immediately after reading
- [x] 1.3 Render the session message as the primary error text; fall back to "Access could not be completed." when the key is absent
- [x] 1.4 Add "Please contact an administrator to request access." as a fixed sub-message below the primary error
- [x] 1.5 Add a "Back to Login" button/link pointing to `/admin/login.php`

## 2. Callback Changes

- [x] 2.1 Add `oidc_provision_fail(string $msg): never` helper in `admin/auth/callback.php` — writes `$_SESSION['oidc_provision_error']`, redirects to `/admin/auth/error.php`, exits
- [x] 2.2 Replace the `oidc_fail()` call on email-not-found (line ~118) with `oidc_provision_fail()`; set a clear message naming the unregistered email
- [x] 2.3 Add a role check after the `is_active` guard: `if (empty($user['role']))` → call `oidc_provision_fail()` with a "no role assigned" message

## 3. Verification

- [ ] 3.1 Manually test: OIDC flow with an email not in `auth_users` → lands on error page with correct message
- [ ] 3.2 Manually test: OIDC flow with a user whose `role` is empty → lands on error page with correct message
- [ ] 3.3 Manually test: Protocol error (wrong state) → still lands on login page with flash error (existing path unchanged)
- [ ] 3.4 Verify direct navigation to `/admin/auth/error.php` (no session key) shows generic fallback and login link without PHP errors
<!-- manual verification required -->
