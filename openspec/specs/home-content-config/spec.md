# home-content-config

## Purpose

`config/home.json` drives all static content on the home page — intro copy, welcome block, and an ordered typed sections array (`text | video | noticeboard | links`). Component types `noticeboard` and `links` render placeholder divs for future dynamic injection. `config/external_links.json` exists as a deployer-managed file but is not loaded by `index.php` directly. Neither file is writable via the admin panel.

## Requirements

### Requirement: home.json schema
`config/home.json` SHALL be the single source of truth for all static copy on the home page. It SHALL contain: `about` (string — short intro paragraph), `welcome` (object with `heading` string and `body` array of paragraph strings), `section_cols` (integer — column-count hint for layout), `sections` (ordered array of section objects), and `partners` (array of `{img, alt, label}` objects).

Each section object in `sections` SHALL have: `type` (one of `"text"`, `"video"`, `"noticeboard"`, `"links"`), `heading` (string), `body` (array of paragraph strings). Type-specific optional fields: `img` (string, for `text`); `provider` (`"youtube"` or `"vimeo"`) and `url` (embed URL string, for `video`). The `url` field on non-video types MAY be `null`.

This file SHALL be edited by deployers only and SHALL NOT be written by any PHP page-serving script.

#### Scenario: File decodes to expected top-level keys
- **WHEN** `config/home.json` is read via `json_decode(file_get_contents(...), true)`
- **THEN** the resulting array SHALL contain the keys `about`, `welcome`, `sections`, and `partners`

#### Scenario: about is a string
- **WHEN** `home.json → about` is accessed
- **THEN** it SHALL be a non-empty string

#### Scenario: welcome.body is an array
- **WHEN** `home.json → welcome.body` is accessed
- **THEN** it SHALL be a PHP array (not a scalar string), allowing multiple paragraphs

#### Scenario: sections is an ordered array
- **WHEN** `home.json → sections` is accessed
- **THEN** it SHALL be a PHP array (not an associative object), where each element has a `type` key and a `heading` key

#### Scenario: text section has img field
- **WHEN** a section with `type = "text"` is inspected
- **THEN** it MAY have an `img` key (relative path to the section image)

#### Scenario: video section has provider and url fields
- **WHEN** a section with `type = "video"` is inspected
- **THEN** it SHALL have a `provider` key (`"youtube"` or `"vimeo"`) and a `url` key (embed URL or empty string if not yet configured)

#### Scenario: noticeboard and links sections render placeholders
- **WHEN** `index.php` renders a section with `type = "noticeboard"` or `type = "links"`
- **THEN** it SHALL output a `<div class="component-placeholder" data-component="…">` element and SHALL NOT query any DB table or read any additional JSON file for that section

#### Scenario: Partners entries have img, alt, and label keys
- **WHEN** each element of `home.json → partners` is inspected
- **THEN** every element SHALL have non-empty string keys `img` (logo image path), `alt` (image alt text), and `label` (display name)

### Requirement: index.php reads home.json directly
`index.php` SHALL read `config/home.json` using `json_decode(file_get_contents(__DIR__ . '/config/home.json'), true)` and use the decoded array for all home-page static text. It SHALL NOT use `cfg()` to access home-page copy, and it SHALL NOT query any DB table to retrieve static welcome or section copy.

#### Scenario: Welcome heading rendered from home.json
- **WHEN** `index.php` is rendered and `home.json → welcome.heading` is `"Welcome to ITC Rahara"`
- **THEN** the HTML response SHALL contain that heading string (escaped via `h()`)

#### Scenario: No DB query for static copy
- **WHEN** `index.php` executes
- **THEN** no SQL statement SHALL SELECT from `content_blocks` or any other table for welcome/section text

#### Scenario: Multiple welcome paragraphs rendered
- **WHEN** `home.json → welcome.body` is an array of two strings
- **THEN** `index.php` SHALL render two `<p>` elements for the welcome body

### Requirement: external_links.json is a deployer-managed file
`config/external_links.json` SHALL exist as a deployer-managed list of popular links (`{label, url}` objects). `index.php` SHALL NOT load this file directly — the "Most Popular Links" section on the home page is rendered as a `type: "links"` component placeholder pending a future dynamic component implementation.

#### Scenario: File exists and is valid JSON
- **WHEN** `config/external_links.json` is read and decoded
- **THEN** it SHALL be a PHP array (not an object)

#### Scenario: Every entry has label and url
- **WHEN** each element of the decoded array is inspected
- **THEN** every element SHALL have non-empty string keys `label` and `url`

#### Scenario: index.php does not load external_links.json
- **WHEN** `index.php` is read
- **THEN** it SHALL NOT contain a `file_get_contents` or `json_decode` call targeting `external_links.json`

### Requirement: home.json and external_links.json are deployer-only files
Neither `home.json` nor `external_links.json` SHALL be writable via the admin panel UI. The admin panel SHALL NOT display form fields or save endpoints for these files. Their deployer-only nature SHALL be documented in the project README.

#### Scenario: No admin route writes home.json
- **WHEN** all files under `admin/` are searched for `home.json`
- **THEN** no `fopen`, `file_put_contents`, or `json_encode` call targeting `home.json` SHALL be found

#### Scenario: No admin route writes external_links.json
- **WHEN** all files under `admin/` are searched for `external_links.json`
- **THEN** no write operation targeting that file SHALL be found
