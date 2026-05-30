# carousel-management

## Purpose

Admin UI for managing carousel slides. Allows `admin` and `sa` roles to upload images, edit captions, and delete slides. Images are stored in `assets/img/carousel/`; captions are persisted in `config/slides.json` as a flat `{filename: caption}` object.

## ADDED Requirements

### Requirement: Role gate

`admin/carousel.php` SHALL call `require_auth('sa', 'admin')` before any output. Unauthenticated requests SHALL redirect to the login page. Requests from roles below `admin` SHALL receive a 403 response.

#### Scenario: Unauthenticated user is redirected
- **WHEN** a visitor with no session requests `admin/carousel.php`
- **THEN** the response SHALL redirect to `admin/login.php`

#### Scenario: Faculty role receives 403
- **WHEN** a logged-in user with role `faculty` requests `admin/carousel.php`
- **THEN** the response SHALL be a 403 page

#### Scenario: Admin role is admitted
- **WHEN** a logged-in user with role `admin` requests `admin/carousel.php`
- **THEN** the carousel management page SHALL render

#### Scenario: SA role is admitted
- **WHEN** a logged-in user with role `sa` requests `admin/carousel.php`
- **THEN** the carousel management page SHALL render

### Requirement: Slide list

`admin/carousel.php` SHALL discover all image files in `assets/img/carousel/` using `glob()` with extensions `jpg`, `jpeg`, `png`, `gif`, `webp`. Each discovered image SHALL be displayed with its filename and its caption from `config/slides.json` (empty if no entry exists). Images SHALL be listed in `natsort()` order.

#### Scenario: Images in folder appear in the list
- **WHEN** `assets/img/carousel/` contains `campus.jpg` and `hall.png`
- **THEN** both files SHALL appear in the carousel management list

#### Scenario: Folder-dropped image shows empty caption
- **WHEN** `campus.jpg` exists in `assets/img/carousel/` but has no entry in `slides.json`
- **THEN** the list SHALL display `campus.jpg` with a blank caption field

#### Scenario: Existing caption pre-fills the input
- **WHEN** `slides.json` contains `{"campus.jpg": "Our Campus"}`
- **THEN** the caption input for `campus.jpg` SHALL be pre-filled with `"Our Campus"`

#### Scenario: Non-image files are not listed
- **WHEN** `assets/img/carousel/` contains `readme.txt` and `campus.jpg`
- **THEN** only `campus.jpg` SHALL appear in the list

#### Scenario: Empty folder shows no slides
- **WHEN** `assets/img/carousel/` is empty (contains only `.gitkeep`)
- **THEN** the slide list SHALL be empty and a suitable empty-state message SHALL be displayed

### Requirement: Image upload

`admin/carousel.php` SHALL accept `multipart/form-data` POST requests to upload a new image. The uploaded file SHALL be validated for MIME type (jpg, jpeg, png, gif, webp) and maximum size (5 MB). On success the file SHALL be saved to `assets/img/carousel/` using its sanitised original filename. On failure a flash error SHALL be set and the page SHALL redirect back.

#### Scenario: Valid image is saved to carousel folder
- **WHEN** an admin uploads a valid `jpg` file under 5 MB
- **THEN** the file SHALL be saved to `assets/img/carousel/` and appear in the slide list on next load

#### Scenario: Invalid MIME type is rejected
- **WHEN** an admin uploads a `.pdf` file
- **THEN** the file SHALL NOT be saved and a flash error SHALL indicate the file type is not allowed

#### Scenario: Oversized image is rejected
- **WHEN** an admin uploads a `jpg` file larger than 5 MB
- **THEN** the file SHALL NOT be saved and a flash error SHALL indicate the file exceeds the size limit

#### Scenario: Filename is sanitised on save
- **WHEN** an admin uploads a file named `my photo (1).jpg`
- **THEN** the saved filename SHALL contain no spaces or special characters

### Requirement: Caption edit

`admin/carousel.php` SHALL accept a POST request containing a filename and a caption string. The file SHALL already exist in `assets/img/carousel/`. On success `config/slides.json` SHALL be updated so that the filename key maps to the submitted caption. An empty caption submission SHALL remove the key from `slides.json`.

#### Scenario: Caption is saved to slides.json
- **WHEN** an admin submits `caption = "Main Building"` for `campus.jpg`
- **THEN** `slides.json` SHALL contain `{"campus.jpg": "Main Building"}`

#### Scenario: Caption for a different image is not overwritten
- **WHEN** `slides.json` contains `{"hall.png": "Assembly Hall"}` and admin saves a caption for `campus.jpg`
- **THEN** `slides.json` SHALL contain both entries

#### Scenario: Empty caption removes the key
- **WHEN** an admin submits an empty caption for `campus.jpg`
- **THEN** `slides.json` SHALL NOT contain the key `"campus.jpg"`

#### Scenario: Caption for non-existent file is rejected
- **WHEN** a POST targets a filename that does not exist in `assets/img/carousel/`
- **THEN** the save SHALL be rejected with a flash error and `slides.json` SHALL be unchanged

### Requirement: Slide deletion

`admin/carousel.php` SHALL accept a POST request to delete a slide by filename. The image file SHALL be removed from `assets/img/carousel/`. If the filename has an entry in `config/slides.json` that entry SHALL also be removed. A flash success message SHALL confirm the deletion.

#### Scenario: Image file is deleted
- **WHEN** an admin deletes `campus.jpg`
- **THEN** `assets/img/carousel/campus.jpg` SHALL no longer exist

#### Scenario: slides.json entry is removed on delete
- **WHEN** `slides.json` contains `{"campus.jpg": "Our Campus"}` and admin deletes `campus.jpg`
- **THEN** `slides.json` SHALL NOT contain the key `"campus.jpg"`

#### Scenario: Deleting an image with no caption leaves slides.json unchanged
- **WHEN** `campus.jpg` has no entry in `slides.json` and admin deletes it
- **THEN** `slides.json` SHALL remain unchanged

#### Scenario: Deleting a non-existent file returns an error
- **WHEN** a delete POST targets a filename not present in `assets/img/carousel/`
- **THEN** a flash error SHALL be set and no file system or JSON change SHALL occur

### Requirement: CSRF protection

Every POST action on `admin/carousel.php` (upload, caption save, delete) SHALL include a CSRF token field. The handler SHALL validate the submitted token against `$_SESSION['csrf']` using `hash_equals()` before processing. Requests with a missing or mismatched token SHALL be rejected with a 400 response.

#### Scenario: Valid CSRF token allows the action
- **WHEN** a POST includes a CSRF token matching the session token
- **THEN** the action SHALL proceed normally

#### Scenario: Missing CSRF token is rejected
- **WHEN** a POST omits the CSRF token field
- **THEN** the response SHALL be 400 and no file or JSON change SHALL occur

#### Scenario: Tampered CSRF token is rejected
- **WHEN** a POST includes a CSRF token that does not match the session token
- **THEN** the response SHALL be 400 and no file or JSON change SHALL occur
