## ADDED Requirements

### Requirement: Page list
`admin/pages.php` SHALL list all designer pages found in `config/public/*.json`. It SHALL display the slug, page title, and last-modified timestamp for each page. It SHALL provide links to create a new page and to edit or delete each existing page. Access SHALL be restricted to `admin` and `sa` roles.

#### Scenario: List shows all pages
- **WHEN** `config/public/` contains `about.json` and `courses.json`
- **THEN** the page list SHALL display two rows, one for each slug

#### Scenario: Empty state when no pages exist
- **WHEN** `config/public/` is empty or does not exist
- **THEN** the page list SHALL display an empty-state message prompting the admin to create the first page

#### Scenario: Role enforcement on page list
- **WHEN** a user with role `faculty` or `user` requests `admin/pages.php`
- **THEN** the response SHALL redirect to `admin/403.php`

---

### Requirement: Create or edit a designer page
`admin/page_designer.php` SHALL provide a form for creating a new page or editing an existing one. In create mode the slug field is editable; in edit mode the slug is displayed read-only. It SHALL present a layout template selector and slot panels driven by the active layout pack manifest. Access SHALL be restricted to `admin` and `sa` roles.

#### Scenario: Load existing page into designer
- **WHEN** admin navigates to `admin/page_designer.php?slug=about` and `config/public/about.json` exists
- **THEN** the designer SHALL populate the slug field, title, description, layout template selection, and all slot panels pre-filled from the saved `slots` dict

#### Scenario: Create mode starts with empty slot panels
- **WHEN** admin navigates to `admin/page_designer.php` without a slug parameter
- **THEN** the designer SHALL default to the first available layout template and render its slot panels empty

#### Scenario: Reserved slug rejected
- **WHEN** admin submits the create form with slug `admin`
- **THEN** the response SHALL display an error and SHALL NOT write any JSON file

#### Scenario: Duplicate slug rejected
- **WHEN** admin submits the create form with a slug that already has a corresponding JSON file in `config/public/`
- **THEN** the response SHALL display an error and SHALL NOT overwrite the existing file

#### Scenario: Invalid slug characters rejected
- **WHEN** admin enters a slug containing uppercase letters, spaces, or special characters other than `-`
- **THEN** the save SHALL fail with a validation error

---

### Requirement: Slot-based layout selection
The designer SHALL present a layout template `<select>` populated from the active layout pack manifest (`assets/css/layouts/{pack}/manifest.json`). Changing the selected template SHALL re-render the slot panels for that template, preserving content in any slot whose ID matches between the old and new template. The layout structure is fixed per template — the admin fills slots, not free-form rows.

#### Scenario: Layout dropdown populated from manifest
- **WHEN** the active layout pack manifest defines templates `landing`, `two-column`, and `full-width`
- **THEN** the layout `<select>` SHALL list those three options with their human-readable labels

#### Scenario: Switching template preserves matching slot content
- **WHEN** admin switches from `landing` (which has a `main` slot) to `two-column` (which also has a `main` slot)
- **THEN** the `main` slot panel SHALL retain its previously entered content; unmatched slots SHALL be empty

#### Scenario: Switching template clears unmatched slots
- **WHEN** admin switches templates and the new template has no slot with ID `hero`
- **THEN** no `hero` slot panel SHALL appear; the content is not lost until Save is clicked

#### Scenario: Slot panels reflect the live preview after change
- **WHEN** admin fills a slot and pauses for 500ms
- **THEN** the preview iframe SHALL refresh to show the slot content rendered inside the layout template

---

### Requirement: HTML column editor
When a column type is set to `html`, the designer SHALL show a raw HTML textarea. The content SHALL allow inline styles and structural HTML tags. On save, the server SHALL strip all `<script>` elements and all `on*` event attributes from the submitted HTML before writing to JSON.

#### Scenario: Inline styles preserved after save
- **WHEN** admin enters `<p style="color:red">Hello</p>` in an html column and saves
- **THEN** `config/public/{slug}.json` SHALL contain `<p style="color:red">Hello</p>` verbatim

#### Scenario: Script tags stripped at save
- **WHEN** admin enters `<p>Hi</p><script>alert(1)</script>` and saves
- **THEN** the stored JSON SHALL contain `<p>Hi</p>` with the `<script>` block removed

#### Scenario: Inline event handlers stripped at save
- **WHEN** admin enters `<a href="#" onclick="evil()">Click</a>` and saves
- **THEN** the stored JSON SHALL contain `<a href="#">Click</a>` with `onclick` removed

---

### Requirement: Embed column editor
When a column type is set to `embed`, the designer SHALL show a sub-type dropdown (YouTube, Vimeo, PDF, MP4, Website) and a URL text field. Selecting a sub-type SHALL update a hint label describing the expected URL format.

#### Scenario: Sub-type selection changes hint
- **WHEN** admin selects "YouTube" from the embed sub-type dropdown
- **THEN** the hint text SHALL indicate that a YouTube embed URL is expected (e.g., `https://www.youtube.com/embed/…`)

#### Scenario: URL field required for embed
- **WHEN** admin saves a column with type `embed` and an empty URL field
- **THEN** the save SHALL fail with a validation error on that column

#### Scenario: All sub-types saved correctly
- **WHEN** admin sets sub-type to `pdf` and enters a URL, then saves
- **THEN** the stored JSON column SHALL have `"type":"embed"`, `"subtype":"pdf"`, and the correct URL

---

### Requirement: Component column editor
When a column type is set to `component`, the designer SHALL show a dropdown listing all available component names discovered by scanning `components/*.php` filenames (stem only, no extension). The admin selects one component from the dropdown.

#### Scenario: Component dropdown lists available components
- **WHEN** `components/` contains `carousel.php` and `noticeboard.php`
- **THEN** the component dropdown SHALL list `carousel` and `noticeboard`

#### Scenario: No manual name entry
- **WHEN** admin views the component column editor
- **THEN** the component name SHALL only be selectable from the dropdown; free-text entry SHALL NOT be permitted

---

### Requirement: Live preview pane
The designer SHALL include a right-side `<iframe>` preview pane. When any layout field changes, the designer SHALL POST the current draft layout JSON to `admin/page_preview.php` after a 500ms debounce and set the iframe's `srcdoc` attribute to the returned HTML.

#### Scenario: Preview updates after debounce
- **WHEN** admin types in the HTML textarea and then pauses for 500ms
- **THEN** the preview iframe SHALL refresh to reflect the new content

#### Scenario: Preview does not fire on every keystroke
- **WHEN** admin types rapidly in any field
- **THEN** only one preview fetch SHALL be sent (the one triggered after 500ms of inactivity)

#### Scenario: Preview shows error on invalid layout
- **WHEN** `admin/page_preview.php` returns `{ "ok": false, "msg": "…" }`
- **THEN** the designer SHALL display the error message as an inline toast and SHALL NOT update the iframe

---

### Requirement: Save page
When the admin submits the page form, the server SHALL validate the slug and layout, write `config/public/{slug}.json`, create `config/public/` if it does not exist, and redirect to `admin/pages.php` with a success flash message. All admin POST handlers SHALL validate the CSRF token before processing.

#### Scenario: Successful save redirects with flash
- **WHEN** admin submits a valid page form
- **THEN** the server SHALL write the JSON file, redirect to `admin/pages.php`, and display a success flash message

#### Scenario: CSRF token mismatch rejected
- **WHEN** a POST request arrives at `admin/page_designer.php` without a valid CSRF token
- **THEN** the server SHALL reject the request with a 403 response

#### Scenario: config/public/ created if absent
- **WHEN** `config/public/` does not exist at save time
- **THEN** the server SHALL create it with `mkdir(…, 0755, true)` before writing the JSON file

---

### Requirement: Delete page
`admin/pages.php` SHALL provide a delete action for each listed page. On confirmation, the server SHALL delete the corresponding `config/public/{slug}.json` file. If the slug appears in `config/menu.json`, the server SHALL display a warning (but SHALL still allow the delete to proceed at admin discretion).

#### Scenario: Delete removes JSON file
- **WHEN** admin confirms deletion of the `about` page
- **THEN** `config/public/about.json` SHALL be removed from the filesystem

#### Scenario: Warning shown when page is in menu
- **WHEN** admin attempts to delete a page whose slug exists in `config/menu.json`
- **THEN** the confirmation prompt SHALL include a warning that the page is linked in the navigation
