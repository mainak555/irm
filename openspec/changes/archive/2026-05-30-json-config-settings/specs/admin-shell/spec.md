## MODIFIED Requirements

### Requirement: Role-aware sidebar navigation
The sidebar SHALL be implemented as a Bootstrap 5 accordion. It SHALL always contain a plain Dashboard link and a plain Profile link visible to all authenticated roles. It SHALL contain an **Authorization** accordion section visible only to users with `role = 'sa'` or `role = 'admin'`. The Authorization section SHALL contain a Users item linking to `/admin/users.php` and a **Configurations** item linking to `/admin/auth_config.php`. The Configurations item SHALL be rendered only when `role = 'sa'`. It SHALL contain a **Settings** accordion section visible only to users with `role = 'sa'`. The Settings section SHALL contain a **General** item linking to `/admin/config_general.php`. The Authorization accordion section SHALL be in the expanded state when the current page is `users` or `auth_config`; collapsed otherwise. The Settings accordion section SHALL be in the expanded state when the current page is any `config_*` page; collapsed otherwise.

#### Scenario: SA sees full sidebar including both accordions
- **GIVEN** a user with role `sa` is authenticated
- **WHEN** they view any admin page
- **THEN** the sidebar SHALL contain a Dashboard link and a Profile link
- **AND** the sidebar SHALL contain an Authorization accordion with Users and Configurations links
- **AND** the sidebar SHALL contain a Settings accordion with a General link

#### Scenario: Admin sees Authorization accordion but no Settings accordion
- **GIVEN** a user with role `admin` is authenticated
- **WHEN** they view any admin page
- **THEN** the sidebar SHALL contain an Authorization accordion with a Users link
- **AND** the Authorization accordion SHALL NOT contain a Configurations link
- **AND** no Settings accordion SHALL be present in the sidebar

#### Scenario: Non-admin roles see no accordions
- **GIVEN** a user with role `faculty` or `user` is authenticated
- **WHEN** they view any admin page
- **THEN** no Authorization accordion SHALL be present in the sidebar
- **AND** no Settings accordion SHALL be present in the sidebar

#### Scenario: Authorization accordion labels sub-item as Configurations not Settings
- **GIVEN** a user with role `sa` or `admin` is authenticated
- **WHEN** they view any admin page
- **THEN** the Authorization accordion SHALL contain a sub-item labelled "Configurations"
- **AND** the Authorization accordion SHALL NOT contain a sub-item labelled "Settings"

#### Scenario: Settings accordion expands on General page
- **GIVEN** a user with role `sa` is authenticated
- **WHEN** they open the General settings page (`/admin/config_general.php`)
- **THEN** the Settings accordion SHALL be in the expanded state
- **AND** the Authorization accordion SHALL be in the collapsed state

#### Scenario: Authorization accordion is expanded on the Users page
- **WHEN** an authenticated SA or admin user is on `/admin/users.php`
- **THEN** the Authorization accordion section SHALL be in the expanded state

#### Scenario: Authorization accordion is expanded on the Configurations page
- **WHEN** an authenticated SA user is on `/admin/auth_config.php`
- **THEN** the Authorization accordion section SHALL be in the expanded state

#### Scenario: Both accordions collapsed on Dashboard
- **WHEN** an authenticated SA user is on `/admin/index.php`
- **THEN** the Authorization accordion SHALL be in the collapsed state
- **AND** the Settings accordion SHALL be in the collapsed state
