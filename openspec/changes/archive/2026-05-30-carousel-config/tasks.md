## 1. Migration

- [x] 1.1 Update `config/slides.json` from `[]` to `{}` (empty flat object — ADR-0018)
- [x] 1.2 Update `admin/_layout.php` Settings accordion role gate from `sa`-only to `['sa', 'admin']`
- [x] 1.3 Add "Carousel" sub-item link to Settings sidebar in `admin/_layout.php` (visible to `admin`+`sa`, active when on `carousel.php`)

## 2. Admin Carousel Page — Shell

- [x] 2.1 Create `admin/carousel.php` with `declare(strict_types=1)`, `require_once` for auth and config, `require_auth('sa', 'admin')`
- [x] 2.2 Wire page into admin layout: `require __DIR__ . '/_layout.php'` and `require __DIR__ . '/_layout_end.php'`
- [x] 2.3 Load and render slide list: `glob('assets/img/carousel/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE)` + `natsort()`, decode `config/slides.json` as flat object, display each image with thumbnail, filename, caption input, and delete button
- [x] 2.4 Show empty-state message when carousel folder has no image files

## 3. Admin Carousel Page — Upload Handler

- [x] 3.1 Add upload form with file input and CSRF token hidden field
- [x] 3.2 Implement POST handler: validate MIME type (jpg/jpeg/png/gif/webp) and file size (≤ 5 MB); reject with flash error on failure
- [x] 3.3 Sanitise original filename (strip spaces and special characters); save to `assets/img/carousel/`
- [x] 3.4 Set flash success and redirect back after successful upload

## 4. Admin Carousel Page — Caption Save Handler

- [x] 4.1 Add caption save form (per-slide) with filename hidden field, caption text input, and CSRF token
- [x] 4.2 Implement POST handler: verify target filename exists in `assets/img/carousel/`; reject with flash error if not
- [x] 4.3 Update `config/slides.json`: set key = filename, value = trimmed caption string; remove key if caption is empty
- [x] 4.4 Set flash success and redirect back after save

## 5. Admin Carousel Page — Delete Handler

- [x] 5.1 Add delete button (POST form) per slide with filename hidden field and CSRF token
- [x] 5.2 Implement POST handler: delete `assets/img/carousel/{filename}`; reject with flash error if file does not exist
- [x] 5.3 Remove filename key from `config/slides.json` if present after file deletion
- [x] 5.4 Set flash success and redirect back after delete

## 6. CSRF Protection

- [x] 6.1 Validate `$_SESSION['csrf']` with `hash_equals()` at the top of every POST branch (upload, caption save, delete); return HTTP 400 on mismatch
- [x] 6.2 Confirm CSRF token is rendered in every form on the page

## 7. Public Carousel Component

- [x] 7.1 Create `public/components/carousel.php` with `declare(strict_types=1)`
- [x] 7.2 Implement glob + `natsort()` image discovery; return immediately with no output if folder is empty
- [x] 7.3 Decode `config/slides.json` as flat object; look up `basename($image)` for each slide caption
- [x] 7.4 Render Bootstrap carousel (`id="irmCarousel"`, `data-bs-ride="carousel"`) with one `.carousel-item` per image; render caption element only when caption string is non-empty; escape all caption output with `h()`
- [x] 7.5 Wrap carousel in Bootstrap column based on `$layout` variable: `'full'` (or unset) → `col-12`; `'col-left'` → `col-md-6 order-md-1`; `'col-right'` → `col-md-6 order-md-2`

## 8. Wire Component into index.php

- [x] 8.1 Replace any existing slides.json reading in `index.php` with the new flat-object format (`json_decode(..., true)` → associative array keyed by filename)
- [x] 8.2 Include the carousel component: `$layout = 'full'; require __DIR__ . '/public/components/carousel.php';` at the appropriate position in the home page body

## 9. Docker Documentation

- [x] 9.1 Add a `docker-compose.example.yml` (or update existing deployment docs) documenting required volume mounts: `/config` and `/assets/img/carousel/`
