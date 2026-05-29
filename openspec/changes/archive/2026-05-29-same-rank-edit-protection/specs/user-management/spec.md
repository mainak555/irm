## ADDED Requirements

### Requirement: Same-rank peer row locking
Any row whose role rank equals the acting user's role rank — and is not the SA sentinel row and
not the actor's own row — SHALL be rendered as a partially locked peer row: the `irm-peer-row`
class SHALL be applied (muted background), all inline controls except the active toggle SHALL be
disabled, and the action menu SHALL be absent. The active toggle SHALL remain enabled and
functional. This check is evaluated at render time per row.

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
SHALL be rejected with HTTP 403 if the target user's role rank equals the acting user's role rank.
The `toggle_active` action SHALL be exempt from this same-rank restriction and SHALL be permitted
for same-rank targets (subject to the existing sentinel and self-edit guards). `sa`-role actors
are unaffected: their same-rank check is not enforced on this page because only `sa` and `admin`
have access and an `sa` actor's rank is strictly higher than any `admin` target.

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
