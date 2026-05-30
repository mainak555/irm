# menu-manager

## Purpose

`admin/menu_manager.php` provides an `sa`-only interface for managing the public site navigation stored in `config/menu.json`. Supports two-level hierarchy (top-level items with optional children), inline reordering, and coordination warnings when menu items link to designer pages.

## Requirements

### Requirement: Menu item list
`admin/menu_manager.php` SHALL display all top-level items from `config/menu.json` in sort-order sequence. For each top-level item that has a `children` array, the children SHALL be displayed indented below their parent. Access SHALL be restricted to the `sa` role only.

#### Scenario: Top-level items displayed in order
- **WHEN** `config/menu.json` contains Home (sort 10), About (sort 20), Courses (sort 30)
- **THEN** the menu manager SHALL list them in that order

#### Scenario: Children displayed below parent
- **WHEN** a top-level item has a `children` array with two entries
- **THEN** both children SHALL be displayed indented under their parent in the list view

#### Scenario: Role enforcement
- **WHEN** a user with role `admin`, `faculty`, or `user` requests `admin/menu_manager.php`
- **THEN** the response SHALL redirect to `admin/403.php`

---

### Requirement: Add or edit top-level menu item
The menu manager SHALL provide a form to add a new top-level item or edit an existing one. The form SHALL include: a label field, a choice between an internal designer-page slug or an external URL, and a sort-order field. For internal links, a slug field SHALL be provided. For external links, a URL field and an "open in new tab" toggle SHALL be provided.

#### Scenario: Save internal menu item
- **WHEN** admin fills in label `About Us`, selects internal, enters slug `about`, and saves
- **THEN** `config/menu.json` SHALL contain an entry with `"label":"About Us"`, `"slug":"about"`, `"is_external":0`

#### Scenario: Save external menu item
- **WHEN** admin fills in label `Portal`, selects external, enters URL `https://portal.school.org`, enables "open in new tab", and saves
- **THEN** `config/menu.json` SHALL contain an entry with `"label":"Portal"`, `"url":"https://portal.school.org"`, `"is_external":1`

#### Scenario: CSRF token validated on save
- **WHEN** a POST to the menu manager arrives without a valid CSRF token
- **THEN** the server SHALL reject the request with a 403 response

---

### Requirement: Add or edit child menu item
The menu manager SHALL allow adding or editing a child item under any top-level item. The form fields are the same as for a top-level item. Only one level of children is permitted — a child item SHALL NOT have its own children.

#### Scenario: Child item added under parent
- **WHEN** admin adds a child with label `UG Courses` and slug `ug-courses` under the `Courses` top-level item
- **THEN** `config/menu.json` SHALL contain `ug-courses` in the `children` array of the `Courses` item

#### Scenario: Two-level limit enforced
- **WHEN** the UI presents a child item
- **THEN** there SHALL be no option to add a sub-child beneath it

---

### Requirement: Delete menu item
The menu manager SHALL allow deleting a top-level item or a child item. When deleting a top-level item that has children, the server SHALL require explicit confirmation that all children will also be removed. When the item's slug matches a file in `config/public/`, the delete confirmation SHALL display a warning that a designer page exists for that slug.

#### Scenario: Delete orphan top-level item
- **WHEN** admin confirms deletion of a top-level item whose slug has no corresponding `config/public/*.json` file
- **THEN** the item SHALL be removed from `config/menu.json` without warning

#### Scenario: Warning shown when page JSON exists
- **WHEN** admin initiates deletion of a menu item whose slug is `about` and `config/public/about.json` exists
- **THEN** the confirmation SHALL display a message that the About page file exists and will remain after the menu item is deleted

#### Scenario: Deleting parent removes children
- **WHEN** admin confirms deletion of a top-level item that has child items
- **THEN** both the parent and all its children SHALL be removed from `config/menu.json`

---

### Requirement: Reorder menu items
The menu manager SHALL allow reordering top-level items using up and down controls. The resulting order SHALL be persisted in the `sort_order` field of each item in `config/menu.json`. Child items SHALL be independently reorderable within their parent's `children` array.

#### Scenario: Move item up decreases sort order
- **WHEN** admin clicks "Move Up" on the second top-level item
- **THEN** `config/menu.json` SHALL be rewritten so that item's `sort_order` is lower than the item above it

#### Scenario: Topmost item cannot be moved up
- **WHEN** admin views the first top-level item in the list
- **THEN** the "Move Up" control SHALL be absent or disabled for that item

---

### Requirement: Persist to config/menu.json
All menu manager write operations (add, edit, delete, reorder) SHALL atomically overwrite `config/menu.json` with the new complete array. The file SHALL be valid JSON. No other file shall be modified by the menu manager.

#### Scenario: File is valid JSON after every write
- **WHEN** any menu manager save operation completes
- **THEN** `config/menu.json` SHALL be parseable by `json_decode`

#### Scenario: config/menu.json created if absent
- **WHEN** `config/menu.json` does not exist and admin saves the first menu item
- **THEN** the file SHALL be created with the saved item as the sole entry
