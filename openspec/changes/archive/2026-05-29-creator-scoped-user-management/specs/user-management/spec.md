## MODIFIED Requirements

### Requirement: Same-rank peer row locking
Any row whose role rank equals the acting user's role rank — and is not the SA sentinel row and
not the actor's own row — SHALL be rendered as a partially locked peer row if and only if the
acting user is not the creator of that row (`created_by ≠ actor.id`). When rendered as a peer
row the `irm-peer-row` class SHALL be applied (muted background), all inline controls except the
active toggle SHALL be disabled, and the action menu SHALL be absent. The active toggle SHALL
remain enabled and functional. If the acting user IS the creator, the row SHALL be rendered fully
unlocked (subject only to the sentinel and self-edit checks). This rule applies uniformly at all
role levels; the previous `sa`-role exemption is removed.

#### Scenario: Actor sees same-rank row they created as fully editable
- **WHEN** the Users page is rendered for an actor
- **AND** the table contains a non-sentinel, non-own user with the same role as the actor
- **AND** that user was created by the actor
- **THEN** that row's inline controls (active toggle, role dropdown, SSO checkbox) are enabled
- **THEN** the action menu (Edit Name, Reset Password, Delete) is present on that row

#### Scenario: Actor sees same-rank row they did not create as partially locked
- **WHEN** the Users page is rendered for an actor
- **AND** the table contains a non-sentinel, non-own user with the same role as the actor
- **AND** that user was not created by the actor
- **THEN** that row is rendered with `irm-peer-row` visual treatment
- **THEN** the role dropdown and SSO checkbox are disabled on that row
- **THEN** the action menu is absent from that row
- **THEN** the active toggle on that row is enabled and clickable

#### Scenario: SA actor sees same-rank SA row they created as fully editable
- **WHEN** the Users page is rendered for an `sa`-role actor
- **AND** the table contains a non-sentinel user with `role = 'sa'` created by that actor
- **THEN** that row's inline controls are enabled
- **THEN** the action menu is present on that row

#### Scenario: SA actor sees same-rank SA row they did not create as partially locked
- **WHEN** the Users page is rendered for an `sa`-role actor
- **AND** the table contains a non-sentinel user with `role = 'sa'` not created by that actor
- **THEN** that row is rendered with `irm-peer-row` visual treatment
- **THEN** the role dropdown and SSO checkbox are disabled on that row
- **THEN** the action menu is absent from that row
- **THEN** the active toggle on that row is enabled and clickable

#### Scenario: Peer row active toggle is still operable
- **WHEN** an actor clicks the active toggle on a same-rank row they did not create
- **THEN** `auth_users.is_active` is toggled for that user
- **THEN** the toggle visually reflects the new state

---

### Requirement: Same-rank peer server guard
All mutation actions (`update_role`, `toggle_sso`, `reset_password`, `delete`, `update_name`)
SHALL be rejected with HTTP 403 if the target user's role rank equals the acting user's role rank
and the target was not created by the acting user (`created_by ≠ actor.id`). If the acting user
IS the creator, all mutation actions SHALL be permitted (subject to the sentinel and self-edit
guards). The `toggle_active` action SHALL be exempt from the same-rank creator restriction and
SHALL be permitted for any same-rank target (subject to the existing sentinel and self-edit
guards). This rule applies uniformly at all role levels; the previous `sa`-role exemption is
removed.

#### Scenario: Mutation on same-rank user not created by actor is rejected
- **WHEN** a user submits any mutation action (`update_role`, `toggle_sso`, `reset_password`, `delete`, `update_name`) targeting a same-rank user they did not create
- **THEN** the server responds with HTTP 403
- **THEN** no data is modified in `auth_users`

#### Scenario: toggle_active on same-rank user not created by actor is permitted
- **WHEN** a user submits a `toggle_active` request targeting a same-rank user they did not create
- **THEN** the server processes the request
- **THEN** `auth_users.is_active` is updated for the target user

#### Scenario: Full mutation on same-rank user created by actor is permitted
- **WHEN** a user submits any mutation action targeting a same-rank user they created
- **THEN** the server processes the request
- **THEN** the corresponding change is applied in `auth_users`

#### Scenario: SA mutation on same-rank SA user not created by actor is rejected
- **WHEN** an `sa`-role user submits any mutation action (`update_role`, `toggle_sso`, `reset_password`, `delete`, `update_name`) targeting a non-sentinel user with `role = 'sa'` they did not create
- **THEN** the server responds with HTTP 403
- **THEN** no data is modified in `auth_users`

#### Scenario: SA mutation on same-rank SA user they created is permitted
- **WHEN** an `sa`-role user submits any mutation action targeting a non-sentinel user with `role = 'sa'` they created
- **THEN** the server processes the request
- **THEN** the corresponding change is applied in `auth_users`

---

### Requirement: Delete user
The action menu for non-SA rows SHALL include a "Delete" option. Selecting it SHALL prompt for
confirmation, then permanently remove the row from `auth_users`. Before the row is deleted, the
server SHALL reassign all rows whose `created_by` equals the target's `id` to `created_by` of
the deleting user (re-assign on delete). The SA row SHALL NOT have a Delete option. Deleting the
currently logged-in user's own account SHALL be blocked server-side.

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

#### Scenario: Deleting a user reassigns their created users to the deleter
- **WHEN** an authorized user deletes User A
- **AND** User A had previously created User B
- **THEN** User B's `created_by` is updated to the deleter's ID before User A is removed
- **THEN** User A is removed from `auth_users`
- **THEN** User B remains in `auth_users` and is now owned by the deleter
