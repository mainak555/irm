## Context

`auth_users` currently has two identity columns:

| Column | SA account | All other users |
|---|---|---|
| `username` | `'admin'` | `NULL` |
| `email` | `NULL` | real email address |

The login form POSTs to `auth_user_find_by_username()`, which performs `WHERE username = :username`. Because non-SA users have `username = NULL`, any login attempt for them returns no row and authentication fails regardless of credentials. SSO users bypass this (they authenticate via OIDC callback), but non-SSO users added through the admin panel are completely locked out.

The fix unifies identity into the `email` column alone: real users get a real email; the SA account gets the reserved sentinel string `'admin'`.

---

## Goals / Non-Goals

**Goals:**
- Non-SSO users can log in using their email address
- The SA account continues to log in by typing `admin` into the login field (no behavior change for existing admins)
- Remove the dead `username` column from schema and all application code
- All SA-guard logic (ordering, locking, protect-from-modification) continues to work correctly

**Non-Goals:**
- Changing the OIDC/SSO callback flow — SSO users already authenticate via `auth_user_find_by_email()` and are unaffected
- Adding email format validation to the login field — `'admin'` is intentionally not a valid email; no format check should be applied at the form level
- Self-service password reset or email verification

---

## Decisions

### Decision 1 — Use `'admin'` string as the SA sentinel in `email`

**Chosen:** Store `email = 'admin'` for the SA account (not a real email address).

**Alternatives considered:**

| Alternative | Why rejected |
|---|---|
| `email = 'admin@localhost'` | Still an email-shaped string; awkward to display on the Users page; no cleaner than `'admin'` |
| Keep a separate boolean `is_sa` flag | Adds a column; SA status is already expressed by `role = 'sa'`; double-guards create drift risk |
| Use `NULL` for SA email and detect by role | `email UNIQUE` constraint breaks with `NULL` on MariaDB 10.3 when using `NOT NULL`; also loses the single-column lookup |

**Why `'admin'` works:** The login lookup is `WHERE email = :email`. Typing `admin` in the form resolves to the SA row. All other users type their real email. No format validation at the form level is needed.

**SA detection predicate** (replaces `username = 'admin'` everywhere):
```sql
email = 'admin' AND role = 'sa'
```

---

### Decision 2 — Rename the login form field to "Email / Username"

The field formerly labelled "Username" now accepts either `admin` or a real email. The label "Email / Username" communicates both cases without requiring two separate fields or branching logic.

---

### Decision 3 — Reuse existing `auth_user_find_by_email()` for all logins

`includes/db_login.php` already has `auth_user_find_by_email()` used by the OIDC callback. The login POST handler switches from `auth_user_find_by_username()` to `auth_user_find_by_email()`. The username-based function is then deleted entirely.

---

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| Existing live DB has `username = 'admin'` row with `email = NULL` — migration must back-fill `email` before dropping the column | Migration plan step 1 runs `UPDATE auth_users SET email = 'admin' WHERE username = 'admin'` before the `ALTER TABLE DROP COLUMN` |
| `schema.sql` is a hard-reset script (DROP/CREATE) — running it on a live DB destroys all users | Documented in the existing schema header; unchanged behaviour. No new risk introduced |
| The sentinel `'admin'` could conflict if someone adds a user with email `'admin'` via the admin panel | `users_ajax.php` `add_user` action already validates `filter_var($email, FILTER_VALIDATE_EMAIL)` — `'admin'` fails that check, so it can never be inserted as a regular user email |

---

## Migration Plan

For **fresh installs** (`mysql < sql/schema.sql`): no migration needed — schema.sql creates the table without `username`.

For **existing installs** with data:

```sql
-- Step 1: back-fill SA sentinel email (must run before DROP)
UPDATE auth_users SET email = 'admin' WHERE username = 'admin' AND role = 'sa';

-- Step 2: drop the column
ALTER TABLE auth_users DROP COLUMN username;
```

These two statements are included as a comment in `sql/schema.sql` under a "Live migration" section so deployers can run them against an existing DB.

**Rollback:** Add back the `username` column and re-populate the SA row. No other users had a non-NULL username, so rollback is lossless.

---

## Open Questions

_(none — scope is fully bounded)_
