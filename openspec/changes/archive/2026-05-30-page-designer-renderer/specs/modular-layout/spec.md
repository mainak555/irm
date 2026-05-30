## MODIFIED Requirements

### Requirement: Nav reads from menu.json
`includes/header.php` SHALL read `config/menu.json` and render the primary navigation. When a top-level item has a non-empty `children` array, it SHALL render a Bootstrap 5.3 dropdown with the top-level item as the toggle button and its children as dropdown items. When a top-level item has no children (or an empty `children` array), it SHALL render a plain `<a>` link. The active-state class SHALL be applied to the top-level item whose slug matches `$active_slug`, even when the active page is a child of that item. Every label, URL, and attribute value rendered to HTML SHALL be escaped with `h()`.

#### Scenario: Nav reads from menu.json
- **WHEN** `config/menu.json` has active top-level items
- **THEN** `includes/header.php` SHALL render those items as nav links

#### Scenario: Flat item renders as plain link
- **WHEN** a top-level item has no `children` array (or an empty array)
- **THEN** the nav SHALL render a plain `<a>` element for that item (no dropdown markup)

#### Scenario: Item with children renders Bootstrap dropdown
- **WHEN** a top-level item has a `children` array with at least one entry
- **THEN** the nav SHALL render a `<li class="nav-item dropdown">` with a `data-bs-toggle="dropdown"` toggle and a `<ul class="dropdown-menu">` containing the children

#### Scenario: Child items rendered as dropdown-item links
- **WHEN** a top-level dropdown item has two child entries
- **THEN** the `<ul class="dropdown-menu">` SHALL contain two `<a class="dropdown-item">` elements with the children's labels and resolved URLs

#### Scenario: Active class on parent when child page is active
- **WHEN** `$active_slug` matches the slug of a child item inside a dropdown parent
- **THEN** the parent `<li>` SHALL receive the active class (not the child link)

#### Scenario: External links open in new tab
- **WHEN** a menu item (top-level or child) has `is_external` equal to `1`
- **THEN** its rendered `<a>` tag SHALL have `target="_blank"` and `rel="noopener"`

## REMOVED Requirements

### Requirement: Any public page can use header and footer
**Reason:** `page.php` is retired as part of the page-designer-renderer change. The scenario asserting `page.php` uses both includes is no longer valid.
**Migration:** `index.php` is the sole public routing entry point. All public pages are rendered through `index.php` using `includes/header.php` and `includes/footer.php`. The general requirement that any PHP page can use the includes is preserved; only the `page.php`-specific scenario is removed.

#### Scenario: index.php uses both includes
- **WHEN** `index.php` is loaded
- **THEN** it SHALL contain `require_once 'includes/header.php'` and `require_once 'includes/footer.php'`

#### Scenario: Resulting HTML is well-formed
- **WHEN** a designer page is rendered via `index.php`
- **THEN** the output SHALL have exactly one `<html>`, one `<head>`, one `<body>`, and the corresponding closing tags
