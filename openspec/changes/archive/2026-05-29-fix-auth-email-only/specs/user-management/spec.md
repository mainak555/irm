## MODIFIED Requirements

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
