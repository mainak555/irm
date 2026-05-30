## 1. Config file migration

- [x] 1.1 In `config/config.json`, rename the top-level key `school` to `general`
- [x] 1.2 In `config/config.json`, add `"public": { "theme": "classic" }` as a new top-level section
- [x] 1.3 Verify `config/config.json` is valid JSON after edits (`json_decode` check)

## 2. Update cfg() call sites (school → general)

- [x] 2.1 In `includes/header.php`, replace all `cfg('school.` calls with `cfg('general.`
- [x] 2.2 In `includes/footer.php`, replace all `cfg('school.` calls with `cfg('general.`
- [x] 2.3 In `admin/login.php`, replace all `cfg('school.` calls with `cfg('general.`
- [x] 2.4 In `admin/_layout.php`, replace all `cfg('school.` calls with `cfg('general.`
- [x] 2.5 Grep the whole codebase for `cfg('school.` and confirm zero remaining matches

## 3. Admin sidebar — Authorization rename + Settings accordion

- [x] 3.1 In `admin/_layout.php`, rename the Authorization sub-item label "Settings" to "Configurations" (the link to `auth_config.php` is unchanged)
- [x] 3.2 In `admin/_layout.php`, add a Settings accordion below Authorization, visible only when `$role === 'sa'`
- [x] 3.3 The Settings accordion SHALL expand when `$current_page` matches any `config_*` page (use `str_starts_with($current_page, 'config_')`)
- [x] 3.4 Add a General sub-item inside the Settings accordion linking to `/admin/config_general.php`

## 4. Theme pack directory and starter file

- [x] 4.1 Create directory `public/css/themes/`
- [x] 4.2 Create `public/css/themes/classic.css` as a minimal starter (may be empty or a copy of current public base styles)

## 5. Create admin/config_general.php

- [x] 5.1 Add `declare(strict_types=1)` and require `auth.php` + `config.php`; call `require_auth('sa')`
- [x] 5.2 On GET: read `config/config.json` and decode to array
- [x] 5.3 On GET: scan `public/css/themes/*.css` with `glob()` to build the theme pack dropdown options; show a warning if the directory is empty
- [x] 5.4 On GET: render form with fields for `general.title`, `general.subtitle`, `general.logoUrl`, `general.address`, `general.phone`, `general.fax`, `general.email`, `general.social.facebook`, and a `<select>` for `public.theme`
- [x] 5.5 On GET: pre-select the current `public.theme` value in the dropdown
- [x] 5.6 On POST: validate CSRF token with `hash_equals()`; abort with 403 on mismatch
- [x] 5.7 On POST: read current `config/config.json`; merge submitted fields into only `general` and `public` sub-arrays — leave `colors` and `footer` untouched
- [x] 5.8 On POST: write backup `file_put_contents('config/config.json.bak', $current_raw)`
- [x] 5.9 On POST: write updated file `file_put_contents('config/config.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX)`
- [x] 5.10 On POST: validate written file with `json_decode(file_get_contents(...))`; if null, restore from `.bak` and set error flash; otherwise set success flash
- [x] 5.11 On POST: redirect back to `config_general.php` after handling (PRG pattern)
- [x] 5.12 Escape all output with `h()`; embed CSRF token in the form as a hidden input
- [x] 5.13 Include `_layout.php` / `_layout_end.php` for admin chrome

## 6. Public view — theme pack CSS loading

- [x] 6.1 In the public page header (e.g. `includes/header.php` or equivalent), emit `<link rel="stylesheet" href="/public/css/themes/<?= h(cfg('public.theme', 'classic')) ?>.css">` after the base stylesheet
- [x] 6.2 Verify fallback: if `cfg('public.theme')` returns an empty string or the file does not exist, the output still links to `classic.css`

## 7. Live smoke-test

- [x] 7.1 Load any public page and confirm the active theme pack CSS link appears in the `<head>`
- [x] 7.2 Log in as `sa`, navigate to Settings → General, confirm the form loads with current values
- [x] 7.3 Submit the form and confirm the success flash and that `config/config.json` is updated
- [x] 7.4 Confirm `config/config.json.bak` exists after a save
- [x] 7.5 Log in as `admin` and confirm the Settings accordion is absent from the sidebar
- [x] 7.6 Confirm the Authorization sub-item is labelled "Configurations" (not "Settings") for both `sa` and `admin` roles
