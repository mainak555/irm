## Purpose

Defines the behaviour of the Users management section of the admin panel: listing, creating, editing, and deleting `auth_users` rows, and inline toggling of active state, role, and SSO flag. Only `sa` and `admin` roles have access.

---

## Requirements

### Requirement: User listing table
The system SHALL display all rows from `auth_users` in a paginated Material Shadcn-styled table. The SA row (identified by `email = 'admin'` AND `role = 'sa'`) SHALL always be rendered first regardless of sort order, with a visually distinct dimmed/muted background. The Email column for the SA row SHALL display `—` (em-dash) since `'admin'` is not a real email address. All other rows SHALL be ordered by `name` ascending by default. The logged-in user's own row SHALL be rendered with all inline controls locked (disabled or absent) and a "You" indicator, preventing self-modification from this page. The `username` column SHALL NOT be referenced anywhere in this page or its AJAX handler.

#### Scenario: SA row is pinned at top
- **WHEN** an authorized user loads the Users page
- **THEN** the SA row is the first row in the table regardless of how many other users exist

#### Scenario: SA row has distinct visual treatment
- **WHEN** the Users page is rendered
- **THEN** the SA row uses the `--irm-muted` background token and its action controls for delete, deactivate, and SSO toggle are absent or disabled

#### Scenario: SA email column shows dash
- **WHEN** the Users page is rendered for the SA row
- **THEN** the Email column displays `—` rather than the sentinel string `'admin'`

#### Scenario: Own row has locked controls
- **WHEN** the Users page is rendered for the logged-in user's own row
- **THEN** the active toggle, SSO checkbox, and role dropdown are disabled or replaced with read-only display
- **THEN** the action menu (Edit Name, Reset Password, Delete) is absent
- **THEN** a "You" indicator is visible on the row

#### Scenario: Table shows all users
- **WHEN** `auth_users` contains N rows
- **THEN** the table renders all N rows with columns: Serial, Active, Name, Email, Role, SSO, Updated, Actions

---

### Requirement: Access control
The Users page SHALL only be accessible to users with `role = 'sa'` or `role = 'admin'`. Any other authenticated role SHALL receive a 403 response. Unauthenticated visitors SHALL be redirected to the login page.

#### Scenario: Admin role can access users page
- **WHEN** a user with `role = 'admin'` navigates to `/admin/users.php`
- **THEN** the users table is rendered

#### Scenario: Faculty role is blocked
- **WHEN** a user with `role = 'faculty'` navigates to `/admin/users.php`
- **THEN** HTTP 403 is returned and the 403 page is rendered

#### Scenario: Unauthenticated visitor is redirected
- **WHEN** a visitor with no active session navigates to `/admin/users.php`
- **THEN** they are redirected to `/admin/login.php`

---

### Requirement: Add user
The system SHALL provide an "Add User" control that opens a modal form. The form SHALL collect
Name, Email, Password, Role (dropdown defaulting to `user`), and SSO flag (checkbox). The role
dropdown SHALL only include roles with rank less than or equal to the acting user's rank — an
`admin`-role actor SHALL NOT see the `sa` option. On valid submission the system SHALL insert a
new `auth_users` row with `is_active = 1` and the bcrypt-hashed password. Email SHALL be unique;
duplicate email submission SHALL be rejected with an error. The server SHALL reject with HTTP 403
any submission where the requested role rank exceeds the acting user's role rank.

#### Scenario: Add user modal opens
- **WHEN** an authorized user clicks "Add User"
- **THEN** a modal form appears with Name, Email, Password, Role (default `user`), and SSO fields

#### Scenario: Admin role dropdown omits sa option
- **WHEN** an `admin`-role user opens the Add User modal
- **THEN** the role dropdown does not include the `sa` option

#### Scenario: User created successfully
- **WHEN** the form is submitted with a unique email and a password that passes complexity rules
- **THEN** a new `auth_users` row is inserted with `is_active = 1`
- **THEN** the modal closes and the table refreshes showing the new row

#### Scenario: Duplicate email rejected
- **WHEN** the form is submitted with an email already present in `auth_users`
- **THEN** the form is re-displayed with an error and no row is inserted

#### Scenario: Weak password rejected on add
- **WHEN** the form is submitted with a password that fails complexity rules
- **THEN** the form is re-displayed with a validation error and no row is inserted

#### Scenario: SSO user created without password
- **WHEN** the SSO checkbox is checked and the form is submitted
- **THEN** the password field is not required and no bcrypt hash is stored

#### Scenario: Admin attempt to add sa-role user is rejected server-side
- **WHEN** an `admin`-role user submits an `add_user` request with `role = 'sa'`
- **THEN** the server responds with HTTP 403
- **THEN** no user is created

---

### Requirement: Inline active toggle
Each non-SA, non-own row SHALL include a single-click active toggle (e.g., a toggle switch in the Active column). Clicking it SHALL immediately send a POST request updating `is_active` for that user in the DB and visually reflect the new state without a full page reload. The SA row toggle and the logged-in user's own row toggle SHALL be disabled.

#### Scenario: Deactivate a non-SA user
- **WHEN** an authorized user clicks the active toggle on a row where `is_active = 1`
- **THEN** `auth_users.is_active` is set to `0` for that user
- **THEN** the toggle visually switches to inactive state

#### Scenario: Reactivate a user
- **WHEN** an authorized user clicks the active toggle on a row where `is_active = 0`
- **THEN** `auth_users.is_active` is set to `1` for that user

#### Scenario: SA active toggle is not operable
- **WHEN** the Users page is rendered for the SA row
- **THEN** the active toggle is disabled and clicking it has no effect

#### Scenario: Own active toggle is not operable
- **WHEN** the Users page is rendered for the logged-in user's own row
- **THEN** the active toggle is disabled and clicking it has no effect

---

### Requirement: Inline role assignment
Each non-SA, non-own row SHALL render a role dropdown in the Role column pre-selected to the user's current role. Changing the selection SHALL immediately POST the new role to the server and update `auth_users.role`. Valid roles are the four enum values: `sa`, `admin`, `faculty`, `user`. The SA row and the logged-in user's own row SHALL show the role as a read-only badge.

#### Scenario: Role changed via dropdown
- **WHEN** an authorized user changes the role dropdown for a non-SA row
- **THEN** `auth_users.role` is updated to the selected value
- **THEN** the dropdown remains at the selected value

#### Scenario: SA role is not changeable
- **WHEN** the Users page is rendered for the SA row
- **THEN** the role column shows the SA role as read-only text or a disabled control

#### Scenario: Own role is not changeable from this page
- **WHEN** the Users page is rendered for the logged-in user's own row
- **THEN** the role column shows a read-only badge; no dropdown is rendered

---

### Requirement: Inline SSO flag toggle
Each non-SA, non-own row SHALL include a checkbox in the SSO column reflecting `auth_users.sso`. Toggling the checkbox SHALL immediately POST the change and update the DB. The SA row and the logged-in user's own row SSO checkbox SHALL be disabled.

#### Scenario: SSO flag enabled
- **WHEN** an authorized user checks the SSO checkbox on a non-SA row
- **THEN** `auth_users.sso` is set to `1` for that user

#### Scenario: SSO flag disabled
- **WHEN** an authorized user unchecks the SSO checkbox on a non-SA row
- **THEN** `auth_users.sso` is set to `0` for that user

#### Scenario: SA SSO checkbox is not operable
- **WHEN** the Users page is rendered for the SA row
- **THEN** the SSO checkbox is absent or disabled

#### Scenario: Own SSO checkbox is not operable
- **WHEN** the Users page is rendered for the logged-in user's own row
- **THEN** the SSO checkbox is disabled and clicking it has no effect

---

### Requirement: Edit user name
Each non-SA, non-own row SHALL expose an "Edit" action (via a 3-dot / vertical ellipsis action menu). Selecting it SHALL allow the user's Name to be updated. On save the change SHALL be committed to `auth_users.name`. The logged-in user's own name SHALL be managed via the Profile page, not the Users page.

#### Scenario: Name updated successfully
- **WHEN** an authorized user opens the action menu, selects "Edit", changes the name, and saves
- **THEN** `auth_users.name` is updated to the new value
- **THEN** the table row reflects the updated name

#### Scenario: Empty name rejected
- **WHEN** the edit form is submitted with a blank name
- **THEN** the form is re-displayed with a validation error and the name is not changed

---

### Requirement: Reset password for non-SSO users
The action menu for non-SSO, non-SA rows SHALL include a "Reset Password" option. Selecting it SHALL generate a random 10-character password that satisfies all complexity rules (`PWD_REGEX`: ≥8 chars, uppercase, digit, special char), store its bcrypt hash in `auth_users.password`, and display the plaintext password to the acting admin exactly once in a dismissible on-screen panel or modal with a "Copy" button. The plaintext SHALL NOT be persisted or logged.

#### Scenario: Reset password shown once
- **WHEN** an authorized user selects "Reset Password" for a non-SSO user
- **THEN** a new 10-character password meeting complexity rules is generated
- **THEN** the bcrypt hash is stored in `auth_users.password`
- **THEN** the plaintext is displayed in a dismissible panel with a copy-to-clipboard button
- **THEN** dismissing the panel makes the plaintext irrecoverable from the UI

#### Scenario: Reset password not available for SSO users
- **WHEN** an authorized user opens the action menu for a row where `sso = 1`
- **THEN** "Reset Password" is not present in the menu

#### Scenario: Reset password not available for SA
- **WHEN** the action menu is rendered for the SA row
- **THEN** "Reset Password" is not present

#### Scenario: Generated password meets complexity
- **WHEN** a password is auto-generated by the reset flow
- **THEN** it is exactly 10 characters long
- **THEN** it contains at least one uppercase letter, one digit, and one special character

---

### Requirement: Delete user
The action menu for non-SA rows SHALL include a "Delete" option. Selecting it SHALL prompt for confirmation, then permanently remove the row from `auth_users`. The SA row SHALL NOT have a Delete option. Deleting the currently logged-in user's own account SHALL be blocked server-side.

#### Scenario: Non-SA user deleted
- **WHEN** an authorized user selects "Delete" and confirms for a non-SA row
- **THEN** that row is removed from `auth_users`
- **THEN** the table refreshes without the deleted row

#### Scenario: Delete blocked for SA
- **WHEN** the action menu is rendered for the SA row
- **THEN** the "Delete" option is absent

#### Scenario: Delete blocked for own row
- **WHEN** the Users page is rendered for the logged-in user's own row
- **THEN** the action menu is absent (no Delete option in UI)

#### Scenario: User cannot delete their own account
- **WHEN** an authorized user attempts to delete their own row via a direct server request
- **THEN** the server rejects the request with an error and no row is removed

#### Scenario: Delete requires confirmation
- **WHEN** an authorized user selects "Delete" on a non-SA row
- **THEN** a confirmation dialog is shown before any DB change is made

---

### Requirement: Updated column
The Users table SHALL include an "Updated" column showing `auth_users.updated_at` converted to the browser's local timezone and `auth_users.updated_by` resolved to the modifier's display name. The column SHALL NOT show a timezone label inline; the full context (local time, IANA timezone name, raw UTC) SHALL be available as a `title` tooltip. The `created_at` and `created_by` columns SHALL be stored in the database but SHALL NOT be surfaced in the table or any other UI element on this page. See ADR-0015.

#### Scenario: Updated column shows local timestamp and actor
- **WHEN** the Users page renders a row whose `updated_at` is non-null
- **THEN** the Updated cell displays the timestamp in the browser's local timezone without a timezone label
- **THEN** hovering over the timestamp shows a tooltip containing the local time, the IANA timezone name, and the raw UTC value
- **THEN** if `updated_by` resolves to a user name, that name is shown beneath the timestamp in the Updated cell

#### Scenario: Created timestamp not shown
- **WHEN** the Users page renders
- **THEN** no "Created" column, `created_at` value, or `created_by` name is present in the table output

---

### Requirement: Role hierarchy row locking
Any row whose role rank is strictly higher than the acting user's role rank SHALL be rendered
with the same locked treatment as the SA sentinel row: `irm-sa-row` class (muted background,
reduced opacity), all inline controls disabled, and the action menu absent. Role ranks are:
`sa`=3, `admin`=2, `faculty`=1, `user`=0. This rule is evaluated at render time per row and
is independent of the sentinel and self-edit checks.

#### Scenario: Admin sees a non-sentinel sa-role row with locked controls
- **WHEN** the Users page is rendered for an `admin`-role actor
- **AND** the table contains a non-sentinel user with `role = 'sa'`
- **THEN** that row is rendered with `irm-sa-row` visual treatment (muted background)
- **THEN** all inline controls (active toggle, role dropdown, SSO checkbox) are disabled
- **THEN** the action menu is absent from that row

#### Scenario: SA actor sees non-sentinel sa-role rows as editable
- **WHEN** the Users page is rendered for an `sa`-role actor
- **AND** the table contains a non-sentinel user with `role = 'sa'`
- **THEN** that row's inline controls are enabled (subject only to the sentinel and self-edit rules)

---

### Requirement: Role hierarchy server guard
Every mutation action (`toggle_active`, `update_role`, `toggle_sso`, `reset_password`, `delete`,
`update_name`) SHALL be rejected with HTTP 403 if the target user's role rank is strictly greater
than the acting user's role rank. This check is performed server-side in `guard_target()`,
independently of any UI state.

#### Scenario: Admin mutation of sa-role target is rejected
- **WHEN** an `admin`-role user submits any mutation action targeting a user with `role = 'sa'`
- **THEN** the server responds with HTTP 403
- **THEN** no data is modified

#### Scenario: SA mutation of admin-role target is permitted by hierarchy
- **WHEN** an `sa`-role user submits a mutation action targeting a user with `role = 'admin'`
- **THEN** the server does not reject the request on hierarchy grounds

---

### Requirement: Same-rank peer row locking
Any row whose role rank equals the acting user's role rank — and is not the SA sentinel row and
not the actor's own row — SHALL be rendered as a partially locked peer row: the `irm-peer-row`
class SHALL be applied (muted background), all inline controls except the active toggle SHALL be
disabled, and the action menu SHALL be absent. The active toggle SHALL remain enabled and
functional. This check is evaluated at render time per row. `sa`-role actors are exempt: the
same-rank rule does not apply when the actor role is `sa`.

#### Scenario: Admin sees another admin row as partially locked
- **WHEN** the Users page is rendered for an `admin`-role actor
- **AND** the table contains a non-sentinel, non-own user with `role = 'admin'`
- **THEN** that row is rendered with `irm-peer-row` visual treatment (muted background)
- **THEN** the role dropdown, SSO checkbox are disabled on that row
- **THEN** the action menu (Edit Name, Reset Password, Delete) is absent from that row
- **THEN** the active toggle on that row is enabled and clickable

#### Scenario: SA actor sees other sa-role rows as fully editable
- **WHEN** the Users page is rendered for an `sa`-role actor
- **AND** the table contains a non-sentinel user with `role = 'sa'`
- **THEN** that row is not treated as a peer-locked row (same-rank rule does not apply to `sa`)
- **THEN** inline controls are subject only to the existing sentinel and self-edit rules

#### Scenario: Peer row active toggle is still operable
- **WHEN** an `admin`-role actor clicks the active toggle on a peer `admin`-role row
- **THEN** `auth_users.is_active` is toggled for that user
- **THEN** the toggle visually reflects the new state

---

### Requirement: Same-rank peer server guard
All mutation actions (`update_role`, `toggle_sso`, `reset_password`, `delete`, `update_name`)
SHALL be rejected with HTTP 403 if the target user's role rank equals the acting user's role rank
and the actor's role is not `sa`. The `toggle_active` action SHALL be exempt from this same-rank
restriction and SHALL be permitted for same-rank targets (subject to the existing sentinel and
self-edit guards).

#### Scenario: Admin destructive action on peer admin is rejected
- **WHEN** an `admin`-role user submits a `delete`, `reset_password`, `update_name`, `update_role`, or `toggle_sso` request targeting another user with `role = 'admin'`
- **THEN** the server responds with HTTP 403
- **THEN** no data is modified in `auth_users`

#### Scenario: Admin toggle_active on peer admin is permitted
- **WHEN** an `admin`-role user submits a `toggle_active` request targeting another user with `role = 'admin'`
- **THEN** the server processes the request
- **THEN** `auth_users.is_active` is updated for the target user

#### Scenario: SA toggle_active on another sa-role user is permitted
- **WHEN** an `sa`-role user submits a `toggle_active` request targeting a non-sentinel user with `role = 'sa'`
- **THEN** the server processes the request (hierarchy and same-rank rules both pass)
- **THEN** `auth_users.is_active` is updated for the target user
