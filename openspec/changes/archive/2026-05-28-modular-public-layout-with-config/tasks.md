## 1. Config JSON Files

- [x] 1.1 Create `config/` directory in the project root
- [x] 1.2 Create `config/config.json` with sections: `school` (title, subtitle, address, phone, email, social), `colors` (CSS custom-property key-value pairs), `footer` (copyright, quick_links array)
- [x] 1.3 Populate `config/config.json` with the school's actual name, address, phone, email, and color values extracted from the current hardcoded strings in `index.php` and `includes/header.php`
- [x] 1.4 Create `config/menu.json` as a JSON array of `{label, slug, sort_order}` objects matching the current top-level nav items
- [x] 1.5 Create `config/home.json` with: `about` (string), `welcome` block, `section_cols` (integer), `sections` as typed array of `{type, heading, body, url, img?, provider?}` objects, `partners` array — copy existing static text from `index.php`
- [x] 1.6 Create `config/external_links.json` as a JSON array of `{label, url}` objects — deployer-managed file; not loaded by `index.php` directly (links section is a component placeholder)
- [x] 1.7 Create `config/slides.json` as an empty array `[]` — deployer-managed carousel caption overlay and JSON-only slides; replaces DB `hero_slides` overlay

## 2. Unified Helpers in config.php

- [x] 2.1 Add `cfg(string $key, mixed $default = null): mixed` to root `config.php` (dot-notation traversal of `config/config.json`; static cache)
- [x] 2.2 Add `h(?string $s): string` to root `config.php` (moved from `includes/functions.php`)
- [x] 2.3 Add `menu_url(array $m): string` to root `config.php` (moved from `includes/functions.php`)
- [x] 2.4 Delete `includes/config.php` — `cfg()` is now in `config.php`
- [x] 2.5 Remove `h()` and `menu_url()` from `includes/functions.php` (now in `config.php` which loads first via `db.php → config.php`)
- [x] 2.6 Confirm `cfg('school.title')`, `cfg('colors')`, and `cfg('footer.quick_links')` each return the expected values

## 3. Database Schema Changes

- [x] 3.1 Remove `CREATE TABLE settings` from `sql/schema.sql`
- [x] 3.2 Remove `CREATE TABLE quick_links` from `sql/schema.sql`
- [x] 3.3 Write one-time migration script `sql/migrate_settings_to_json.php` that reads existing `settings` and `quick_links` rows and writes them into `config/config.json` (run before deploying, then delete the script)

## 4. Remove setting() Helper

- [x] 4.1 Delete the `setting()` function from `includes/functions.php`
- [x] 4.2 Replace every `setting('key')` call site across all PHP files with the equivalent `cfg('school.<key>')` or `cfg()` dot-notation path
- [x] 4.3 Verify: search all `*.php` files for the string `setting(` and confirm zero matches remain

## 5. Reusable header.php

- [x] 5.1 Add `declare(strict_types=1)` to `includes/header.php`
- [x] 5.2 Add `require_once __DIR__ . '/config.php';` at the top of `includes/header.php`
- [x] 5.3 Replace any hardcoded school name / title in `<title>` with `h(cfg('school.title'))`
- [x] 5.4 Emit an inline `<style>:root { … }</style>` block inside `<head>` using `cfg('colors')` — each key becomes `--<key>: <value>;` with all values passed through `h()`
- [x] 5.5 Replace DB nav query with direct read of `config/menu.json`; normalise each item for `is_external` and `page_target` defaults
- [x] 5.6 Remove `require_once functions.php` from `includes/header.php` — `h()` and `menu_url()` are now available via `config.php`
- [x] 5.7 Ensure `includes/header.php` opens `<body>` but does NOT close it
- [x] 5.8 Verify: no school name or address string literal remains in `includes/header.php`

## 6. Reusable footer.php

- [x] 6.1 Add `declare(strict_types=1)` to `includes/footer.php`
- [x] 6.2 Add `require_once __DIR__ . '/config.php';` at the top of `includes/footer.php`
- [x] 6.3 Replace any DB query for `quick_links` with a loop over `cfg('footer.quick_links')` — render each as `<a href="<?= h($link['url']) ?>"><?= h($link['label']) ?></a>`
- [x] 6.4 Replace hardcoded address/phone/email with `h(cfg('school.address'))`, `h(cfg('school.phone'))`, `h(cfg('school.email'))`
- [x] 6.5 Replace hardcoded copyright text with `h(cfg('footer.copyright'))`
- [x] 6.6 Ensure `includes/footer.php` closes `</body></html>`
- [x] 6.7 Verify: no address, phone, or copyright string literal remains in `includes/footer.php`

## 7. Carousel Folder

- [x] 7.1 Create `assets/img/carousel/` directory and add a `.gitkeep` file so the folder is tracked in version control
- [x] 7.2 Move (or copy) any existing carousel/hero images into `assets/img/carousel/`

## 8. index.php Refactor

- [x] 8.1 Add `declare(strict_types=1)` to `index.php`
- [x] 8.2 Replace the entire `<head>` block and nav/header markup at the top of `index.php` with `require_once 'includes/header.php';`
- [x] 8.3 Replace the entire footer markup at the bottom of `index.php` with `require_once 'includes/footer.php';`
- [x] 8.4 Replace any remaining `setting()` calls in `index.php` with `cfg()` equivalents
- [x] 8.5 Load home content: `$home = json_decode(file_get_contents(__DIR__ . '/config/home.json'), true);`
- [x] 8.6 Replace all hardcoded copy with values from `$home`; render `about` string and `welcome` block via `h()`
- [x] 8.7 Remove `$links` (external_links.json) loading — the `links` section is a component placeholder; no link list rendered by `index.php`
- [x] 8.8 Render sections via typed array loop dispatching on `type`: text (img + body), video (iframe or placeholder shell), noticeboard/links (`<div data-component="…">`)
- [x] 8.9 Add carousel discovery: `$folder_slides = glob(…); natsort($folder_slides);`
- [x] 8.10 Load `config/slides.json` as caption overlay; for each glob result check if a slides.json entry matches by basename; use JSON caption if found, otherwise derive from filename
- [x] 8.11 Append slides.json entries whose `image_path` basename does not match any glob file (JSON-only slides)
- [x] 8.12 Render partners in a separate `.partners-strip` section below the sections grid (not inside `.latest`)
- [x] 8.12 Ensure the carousel render loop uses `h()` on all image src and caption output
- [x] 8.13 Remove any now-unused DB queries for `content_blocks`, `popular_links`, and `settings` from `index.php`

## 9. page.php Update

- [x] 9.1 Add `declare(strict_types=1)` to `page.php`
- [x] 9.2 Replace the duplicated `<head>` and nav markup at the top of `page.php` with `require_once 'includes/header.php';`
- [x] 9.3 Replace the duplicated footer markup at the bottom of `page.php` with `require_once 'includes/footer.php';`
- [x] 9.4 Remove any `setting()` calls from `page.php`

## 10. Admin Settings Page

- [x] 10.1 Rewrite `admin/settings.php` GET handler to read `config/config.json` and populate the form fields
- [x] 10.2 Rewrite `admin/settings.php` POST handler: validate CSRF token, read current `config.json`, merge submitted fields, write backup to `config/config.json.bak`, write new file with `file_put_contents(..., LOCK_EX)`
- [x] 10.3 After write, call `json_decode(file_get_contents('config/config.json'))` to validate; if null, restore `.bak` and set error flash
- [x] 10.4 Remove any code in `admin/settings.php` that queries or writes the `settings` DB table
- [x] 10.5 Verify the admin settings form has fields for all keys in `config.json` that a non-developer would need to change (title, subtitle, address, phone, email, copyright)

## 11. Menu Seed Script

- [x] 11.1 Create `sql/seed_menu.php`: read `config/menu.json`, check if `menus` table is empty, INSERT each item with correct `label`, `slug`, `sort_order` using PDO named placeholders
- [x] 11.2 Confirm the seed script does nothing if `menus` already has rows

## 12. Verification and Cleanup

- [ ] 12.1 Load the home page in a browser and confirm: header/nav renders, footer renders, carousel shows folder images, welcome copy appears, popular links render
- [ ] 12.2 Confirm changing `config/config.json → school.title` updates the `<title>` tag and header on next page load without touching PHP
- [ ] 12.3 Drop a new image into `assets/img/carousel/` and reload the home page — confirm the new slide appears without any other change
- [x] 12.4 Search all PHP files for `FROM settings` and `FROM quick_links` — confirm zero matches
- [x] 12.5 Search all PHP files for `setting(` — confirm zero matches
- [ ] 12.6 Run `page.php` and confirm it renders with full header/footer chrome and no duplicated markup
