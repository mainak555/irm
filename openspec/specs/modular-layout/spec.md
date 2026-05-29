# modular-layout

## Purpose

Reusable `includes/header.php` and `includes/footer.php` so any public page produces complete, valid HTML with a single `require_once` at top and bottom. Eliminates layout duplication across pages. Header reads nav from `config/menu.json` (DB-free); footer reads contact and quick-link data from `cfg()`.

## Requirements

### Requirement: Reusable header include
`includes/header.php` SHALL render the complete HTML `<head>` block, the top navigation bar, and the site header band in a single `require_once`. It SHALL call `cfg()` for branding values (title, logo alt text) and emit the inline CSS custom-property block. It SHALL open the `<body>` tag and leave the page body unclosed so the calling script can inject its content. Every value from config or DB rendered into the HTML SHALL be wrapped in `h()`.

#### Scenario: Single require_once gives full header chrome
- **WHEN** a PHP page contains only `require_once 'includes/header.php'`
- **THEN** the rendered output SHALL contain `<head>`, `<meta charset`, `<title>`, `</head>`, `<body`, and the primary navigation `<nav>` element

#### Scenario: Page title uses school name from config
- **WHEN** `config.json → school.title` is `"Ramakrishna Mission Boys' Home ITC"`
- **THEN** the `<title>` tag SHALL contain that string (escaped via `h()`)

#### Scenario: Nav reads from menu.json
- **WHEN** `config/menu.json` has active top-level items
- **THEN** `includes/header.php` SHALL render those items as nav links

#### Scenario: No hardcoded school name in header.php
- **WHEN** `includes/header.php` is read
- **THEN** the school name string SHALL NOT appear as a PHP string literal; it SHALL only be output via `cfg('school.title')`

### Requirement: Reusable footer include
`includes/footer.php` SHALL render the complete site footer — including quick links, contact details, copyright notice, and closing `</body></html>` tags — in a single `require_once`. Contact details and copyright SHALL come from `cfg()`. Quick links SHALL come from `cfg('footer.quick_links')`. Every value rendered to HTML SHALL be wrapped in `h()`.

#### Scenario: Single require_once gives full footer chrome
- **WHEN** a PHP page contains only `require_once 'includes/footer.php'`
- **THEN** the rendered output SHALL contain the footer element, the copyright notice, and `</body></html>`

#### Scenario: Quick links rendered from config
- **WHEN** `config.json → footer.quick_links` contains `[{"label":"Home","url":"/"}]`
- **THEN** `includes/footer.php` SHALL render an `<a href="/">Home</a>` link in the footer

#### Scenario: Contact details use cfg()
- **WHEN** `cfg('school.address')` is `"Rahara, Kolkata 700118"`
- **THEN** the footer SHALL display that address string (escaped)

#### Scenario: No hardcoded address or phone in footer.php
- **WHEN** `includes/footer.php` is read
- **THEN** the address and phone strings SHALL NOT appear as PHP string literals

### Requirement: Any public page can use header and footer
Any PHP page in the public web root (e.g., `index.php`, `page.php`) SHALL be able to produce a complete, valid HTML document by requiring `includes/header.php` at the top and `includes/footer.php` at the bottom. No additional markup SHALL be needed for the chrome.

#### Scenario: index.php uses both includes
- **WHEN** `index.php` is loaded
- **THEN** it SHALL contain `require_once 'includes/header.php'` and `require_once 'includes/footer.php'`

#### Scenario: page.php uses both includes
- **WHEN** `page.php` is loaded
- **THEN** it SHALL contain `require_once 'includes/header.php'` and `require_once 'includes/footer.php'`

#### Scenario: Resulting HTML is well-formed
- **WHEN** either page is rendered
- **THEN** the output SHALL have exactly one `<html>`, one `<head>`, one `<body>`, and the corresponding closing tags

### Requirement: No layout markup duplicated across pages
The `<head>` meta block, `<nav>` primary navigation, header band, and footer SHALL NOT be copy-pasted or duplicated across PHP files. These SHALL exist only inside `includes/header.php` and `includes/footer.php` respectively.

#### Scenario: Nav markup exists in exactly one file
- **WHEN** all PHP files are searched for the primary `<nav>` element containing the menu
- **THEN** it SHALL appear only inside `includes/header.php`

#### Scenario: Footer markup exists in exactly one file
- **WHEN** all PHP files are searched for the footer `<footer>` element with copyright
- **THEN** it SHALL appear only inside `includes/footer.php`
