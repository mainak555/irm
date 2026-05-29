## Context

The `auth_users` table currently has no management UI. Only one account (the SA) exists at initial setup, and admins have no way to create or manage additional user accounts. The existing table schema has no `sso` column — SSO users are currently implied by `password IS NULL`, but an explicit flag is needed for UI clarity.

Existing conventions this design must follow: PDO named placeholders, `h()` for output escaping, `$_SESSION['csrf']` + `hash_equals()` on every POST, `require_auth()` guard, `declare(strict_types=1)` on every PHP file, Bootstrap 5.3 + `--irm-*` CSS tokens.

## Goals / Non-Goals

**Goals:**
- Full CRUD for `auth_users` in a single admin page
- Inline active/role/SSO edits via `fetch()` without full reload
- SA row permanently protected at the server and UI layers
- One-time password reveal for reset (never persisted in plaintext)
- Sidebar navigation entry for `sa` and `admin` roles

**Non-Goals:**
- Bulk import/export of users
- Audit log of changes
- Email notifications on account creation or password reset
- Managing OIDC/SAML provider configuration (handled by `auth_config.php`)
- Pagination (initial build; all users shown in one table)

## Decisions

### 1. Inline edits via `fetch()` POST, not form submits

**Decision:** Active toggle, role dropdown, and SSO checkbox each call a lightweight `admin/users_ajax.php` endpoint via `fetch()`. The response is JSON `{"ok": true}` or `{"ok": false, "msg": "..."}`.

**Rationale:** Full page reload on every toggle would feel slow and reset scroll position. A dedicated AJAX file keeps `users.php` clean. Single endpoint with an `action` parameter (`toggle_active`, `update_role`, `toggle_sso`, `delete`, `reset_password`) reduces routing complexity.

**Alternatives considered:**
- One POST per action to `users.php` itself: workable but mixes HTML and JSON output in one file.
- REST-style separate files per action: over-engineered for vanilla PHP with no routing layer.

### 2. Add-user via Bootstrap modal (not inline row)

**Decision:** "Add User" opens a Bootstrap `modal` with SSO checkbox (top), Name, Email, Password (hidden/disabled when SSO checked), and Role dropdown (default `user`). On submit, a `fetch()` POST to `users_ajax.php?action=add_user` is used. Validation errors are shown inline inside the modal (Bootstrap `is-invalid` classes + an alert div); success closes the modal and reloads the page.

**Rationale:** A form POST + redirect closed the modal on every error, forcing the user to re-open it and re-enter data. AJAX keeps the modal open and surfaces field-level errors in place. This is consistent with the inline-edit AJAX pattern used for all other mutations on this page. The `<form method="post">` attribute is retained as a structural anchor; JS intercepts `submit` before any native submission occurs.

**Alternatives considered:**
- Inline new-row at top of table: common in Material Design but validation UX is poor in a table cell.
- Separate `add_user.php` page: unnecessary navigation friction for a small form.
- Form POST + redirect: implemented initially; replaced after UX feedback (modal closed on error).

### 3. Password reset revealed in an on-page dismissible alert

**Decision:** On reset, `users_ajax.php` returns the plaintext password in JSON (over HTTPS session). JS renders it inside a Bootstrap `alert-success` block above the table with the password in a `<code>` span and a "Copy" button (`navigator.clipboard.writeText()`). Dismissing the alert removes it from the DOM. The plaintext is never written to PHP session, log, or DB.

**Rationale:** A modal for the reveal would require the user to close it before continuing, but they may want to keep the value visible while communicating it to the target user. An on-page alert is dismissible on their schedule, stays visible during copy, and is clearly "one time" in UX.

**Alternatives considered:**
- Show in a modal: user must close modal to continue working; harder to keep open.
- Show in a toast: auto-dismisses too quickly; not safe enough for a credential reveal.

### 4. SA protection enforced in PHP, not just JS

**Decision:** `users_ajax.php` re-checks `role='sa'` + `username='admin'` of the target row on every mutating request. Delete, toggle_active, toggle_sso, and update_role all return HTTP 403 if the target `id` matches the SA row. UI suppression is cosmetic only.

**Rationale:** Client-side hiding of controls can be bypassed. Server-side guard is the real constraint.

### 5. `sso` column added to `auth_users`

**Decision:** Add `sso TINYINT(1) NOT NULL DEFAULT 0` to `auth_users`. Schema migration is via `ALTER TABLE` in a migration note; `schema.sql` is updated so fresh installs include it.

**Rationale:** Currently SSO status is inferred from `password IS NULL`, which is fragile. An explicit column makes intent clear, allows querying without joins, and decouples the SSO flag from the password storage state.

### 6. New `includes/db_users.php` module

**Decision:** All `auth_users` CRUD queries live in `includes/db_users.php`, following the existing pattern (`db_login.php`, `db_profile.php`). Functions: `users_list()`, `user_get(int $id)`, `user_create(array $data)`, `user_update_name(int $id, string $name)`, `user_update_role(int $id, string $role)`, `user_toggle_active(int $id)`, `user_toggle_sso(int $id)`, `user_update_password(int $id, string $hash)`, `user_delete(int $id)`.

### 7. Generated password uses `random_int()` + Fisher-Yates shuffle

**Decision:** Password generation uses PHP's `random_int()` (CSPRNG) to pick characters from a charset covering uppercase, lowercase, digits, and specials. The generator guarantees at least one character from each required class (uppercase, digit, special), pads to 10 characters with random chars from the full set, then shuffles the array with a Fisher-Yates shuffle driven by `random_int()`.

**Rationale:** `random_int()` is cryptographically secure on PHP 7+. `rand()` and `mt_rand()` must never be used for credentials. `str_shuffle()` uses `mt_rand()` internally and is therefore not cryptographically safe for shuffling a password; Fisher-Yates with `random_int()` is the correct approach.

## Risks / Trade-offs

- **CSRF on AJAX calls** — `fetch()` POSTs must include the CSRF token in the request body or a header. The token is embedded as a JS variable from PHP on page load. Risk: if JS is disabled, inline edits silently fail. Mitigation: the table still renders; only inline edits are lost (acceptable degradation for an admin panel).
- **Race condition on active toggle** — two admins toggling the same row simultaneously could produce unexpected results. Mitigation: last-write-wins at the DB level; acceptable given low concurrency in a school admin panel.
- **Plaintext password in JSON response** — returned over the same HTTPS session. Risk: visible in browser devtools network tab. Mitigation: this is acceptable for an admin panel password reset flow; the alternative (email delivery) is out of scope.
- **No `sso` column on existing installs** — `users.php` and `users_ajax.php` will break on databases deployed before this change. Mitigation: `ALTER TABLE auth_users ADD COLUMN sso TINYINT(1) NOT NULL DEFAULT 0;` must be run on existing installs; documented in migration plan.

## Migration Plan

1. Run on existing DB: `ALTER TABLE auth_users ADD COLUMN IF NOT EXISTS sso TINYINT(1) NOT NULL DEFAULT 0;`
   *(MySQL 8 supports `IF NOT EXISTS` for columns; for MySQL 5.7 / MariaDB, check first or use a migration script.)*
2. Update `sql/schema.sql` to include the `sso` column so fresh installs are correct.
3. Deploy new files: `admin/users.php`, `admin/users_ajax.php`, `includes/db_users.php`.
4. Update `admin/_layout.php` to add "Users" to the sidebar nav (visible to `sa` and `admin`).

**Rollback:** Remove the three new files. The `sso` column is additive and non-breaking — existing login/OIDC flows do not read it, so it can be left in place.

## Open Questions

- Should `admin` role be allowed to promote users to `sa`? (Current spec does not restrict this. Recommend restricting role assignment to `sa` only — but deferring to implementation decision.)
- Should the `email` field be editable from the Users page, or is that reserved for the user's own profile? (Not in scope for this change; email edit not included.)
