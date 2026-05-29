## ADDED Requirements

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

## MODIFIED Requirements

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
