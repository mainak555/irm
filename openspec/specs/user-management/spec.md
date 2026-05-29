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
- **THEN** the table renders all N rows with columns: Serial, Active, Name, Email, Role, SSO, Actions

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
The system SHALL provide an "Add User" control that opens a modal form. The form SHALL collect Name, Email, Password, Role (dropdown defaulting to `user`), and SSO flag (checkbox). On valid submission the system SHALL insert a new `auth_users` row with `is_active = 1` and the bcrypt-hashed password. Email SHALL be unique; duplicate email submission SHALL be rejected with an error.

#### Scenario: Add user modal opens
- **WHEN** an authorized user clicks "Add User"
- **THEN** a modal form appears with Name, Email, Password, Role (default `user`), and SSO fields

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
