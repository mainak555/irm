# public-routing

## Purpose

`index.php` is the sole public entry point for designer pages. It validates slugs, loads page JSON from `config/public/`, injects SEO meta tags, and renders via `render_page_layout()`. Clean URL rewriting is handled by `.htaccess` backed by an Apache vhost config that enables `AllowOverride All`.

## Requirements

### Requirement: index.php as slug router
`index.php` SHALL read the `p` query parameter (the slug), validate it against the pattern `[a-z0-9-]+`, load `config/public/{slug}.json`, and render the page. When no `p` parameter is present it SHALL default to slug `home`. It SHALL `require_once` `includes/header.php` (passing `$page_title`, `$page_description`, `$canonical_url`) and `includes/footer.php` to produce a complete HTML document.

#### Scenario: Root URL renders home page
- **WHEN** a browser requests `/` (no `p` parameter)
- **THEN** `index.php` SHALL load `config/public/home.json` and render its layout

#### Scenario: /about renders the about page
- **WHEN** a browser requests `/about` (rewritten to `index.php?p=about`)
- **THEN** `index.php` SHALL load `config/public/about.json` and render its layout

#### Scenario: Invalid slug characters return 404
- **WHEN** the `p` parameter contains characters outside `[a-z0-9-]`
- **THEN** `index.php` SHALL respond with HTTP 404

#### Scenario: Missing page JSON returns 404
- **WHEN** the `p` parameter is `events` and `config/public/events.json` does not exist
- **THEN** `index.php` SHALL respond with HTTP 404 and a user-friendly not-found page

---

### Requirement: Reserved slug blocking
Certain slugs SHALL be permanently blocked from use as designer page names. The reserved list is: `admin`, `assets`, `config`, `includes`, `components`, `api`. A request for a reserved slug SHALL return HTTP 404 (not a designer page).

#### Scenario: Reserved slug admin returns 404
- **WHEN** a browser requests `/admin` via the slug router
- **THEN** `index.php` SHALL respond with HTTP 404

#### Scenario: Non-reserved slug is served normally
- **WHEN** a browser requests `/about` and `config/public/about.json` exists
- **THEN** `index.php` SHALL serve the page normally

---

### Requirement: Clean URL rewriting via .htaccess
A `.htaccess` file at the web root SHALL rewrite clean URL slugs to the `index.php?p=` query parameter format. Existing files and directories SHALL pass through without rewriting.

#### Scenario: /about rewrites to index.php?p=about
- **WHEN** the web server receives `GET /about`
- **THEN** Apache SHALL internally rewrite the request to `index.php?p=about`

#### Scenario: Existing static files are not rewritten
- **WHEN** the web server receives `GET /assets/css/site.css`
- **THEN** Apache SHALL serve the file directly without rewriting

#### Scenario: Directories are not rewritten
- **WHEN** the web server receives a request for a path that resolves to a directory
- **THEN** Apache SHALL NOT rewrite it to `index.php`

---

### Requirement: Apache AllowOverride All
A file `apache/000-default.conf` SHALL be provided that configures the Apache `<VirtualHost>` to set `AllowOverride All` for `/var/www/html`. This file SHALL be copied into the Docker image via a `COPY` instruction in the Dockerfile so that `.htaccess` rules take effect inside the container.

#### Scenario: .htaccess rules active in Docker container
- **WHEN** the Docker image is built and a container is started
- **THEN** a clean URL request such as `GET /about` SHALL be rewritten to `index.php?p=about` by mod_rewrite

#### Scenario: AllowOverride missing causes silent failure
- **WHEN** the Apache vhost does NOT set `AllowOverride All`
- **THEN** `.htaccess` rules SHALL be silently ignored and clean URLs SHALL return 404 (this scenario documents the failure mode, not desired behaviour)

---

### Requirement: SEO meta tags from page JSON
For every rendered designer page, `index.php` SHALL emit a `<title>` tag combining the page title from JSON and the site title from `cfg('general.title')`. It SHALL emit `<meta name="description">` from the page JSON `description` field if present. It SHALL emit `<link rel="canonical">` using the full URL constructed from `$_SERVER['HTTP_HOST']` and the slug.

#### Scenario: Title uses page JSON title
- **WHEN** `config/public/about.json` has `"title": "About Us"` and site title is `"Springfield Academy"`
- **THEN** the rendered `<title>` SHALL be `About Us — Springfield Academy`

#### Scenario: Meta description emitted when present
- **WHEN** `config/public/about.json` has `"description": "Learn about our school"`
- **THEN** the rendered HTML SHALL contain `<meta name="description" content="Learn about our school">`

#### Scenario: Meta description omitted when absent
- **WHEN** `config/public/about.json` has no `description` field
- **THEN** no `<meta name="description">` tag SHALL appear in the output

#### Scenario: Canonical link uses current host and slug
- **WHEN** the request host is `school.example.com` and the slug is `about`
- **THEN** the rendered HTML SHALL contain `<link rel="canonical" href="https://school.example.com/about">`

---

### Requirement: page.php retired
`page.php` SHALL be deleted. It queries a `pages` DB table that was never created and has been dead code since its introduction. No other file SHALL reference or include `page.php`.

#### Scenario: page.php does not exist after change applied
- **WHEN** the codebase is inspected after this change is applied
- **THEN** `page.php` SHALL NOT exist in the web root

#### Scenario: No inbound links to page.php
- **WHEN** all PHP and template files are searched for `page.php`
- **THEN** no references SHALL be found
