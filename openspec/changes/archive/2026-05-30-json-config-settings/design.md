## Context

`config/config.json` is the single source of truth for school identity and branding. Currently there is no admin UI to edit it — an `sa` user must hand-edit the file on the server. The `school` top-level key is being renamed to `general` (BREAKING) to align with the new Settings → General admin page. A new `public.theme` key is being added to store the active CSS theme pack slug. The admin sidebar currently has a "Settings" sub-link under Authorization that points to `auth_config.php` (OIDC config); this will be renamed "Configurations" to free the "Settings" label for the new top-level accordion.

Reference: [C4 component diagram](../../../docs/architecture/c4-components-json-config-settings.md)

## Goals / Non-Goals

**Goals:**
- Add Settings → General admin page (`admin/config_general.php`, `sa` only) that edits `general.*` fields and `public.theme`
- Add Settings top-level accordion to admin sidebar; rename Authorization sub-item "Settings" → "Configurations"
- BREAKING: rename `config.json → school` key to `general`; update all 12 `cfg('school.*')` call sites
- Add `config.json → public.theme` key; scaffold `public/css/themes/` with a starter `classic.css`
- Theme pack dropdown populated by filesystem scan (`glob`); no manifest

**Non-Goals:**
- Editing `config.json → colors` (CSS custom property values) — deferred
- Editing `config.json → footer` (copyright, quick_links) — deferred
- Editing `config/home.json` or `config/menu.json` — separate Settings sub-pages, future change
- File upload for logo image — `logoUrl` is a plain text field (URL/path string) for now
- APCu caching of `cfg()` — out of scope

## Decisions

### 1. `school` key renamed to `general` (BREAKING)

The JSON key must match the admin page concept ("General") and avoid the ambiguity of "school" in a generic CMS. All `cfg('school.*')` call sites (12, across 4 files) are updated in the same change. The `config.json` file itself is updated in place — no migration script needed since this is a self-hosted single-file config.

Affected files: `includes/header.php`, `includes/footer.php`, `admin/login.php`, `admin/_layout.php`.

### 2. Write path: `file_put_contents` + `LOCK_EX` + backup (ADR-0005)

On POST, `config_general.php`:
1. Reads current `config/config.json` → decodes to array
2. Merges submitted fields into the `general` and `public` sub-arrays only — `colors` and `footer` are untouched
3. Writes backup: `file_put_contents('config/config.json.bak', ...)`
4. Writes updated file: `file_put_contents('config/config.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX)`
5. Validates: `json_decode(file_get_contents('config/config.json'))` — if null, restores from `.bak` and shows error flash

### 3. Theme pack discovery: filesystem scan (ADR-0017)

`glob(ROOT . '/public/css/themes/*.css')` runs on GET to populate the dropdown. Slug = filename stem (`basename($f, '.css')`). Label = `ucwords(str_replace('-', ' ', $slug))`. The active slug from `cfg('public.theme')` pre-selects the dropdown option. No manifest file.

Public view applies the pack: `<link rel="stylesheet" href="/public/css/themes/<?= h(cfg('public.theme', 'classic')) ?>.css">` — falls back to `classic` if key is absent.

### 4. Sidebar: new Settings accordion, sa-only

The Settings accordion is added to `_layout.php` below the Authorization accordion, visible only when `$role === 'sa'`. It auto-expands when `$current_page` is any `config_*` page. The Authorization "Settings" link is renamed "Configurations" — the only change to the Authorization accordion.

### 5. Spec updates (no new capability)

`static-config` spec is updated: `school` → `general` key rename, `public.theme` addition, write handler filename corrected to `admin/config_general.php`. `admin-shell` spec is updated: Settings accordion requirement added, Configurations rename noted. No new capability spec is created — these are requirement changes to existing capabilities.

## Risks / Trade-offs

| Risk | Mitigation |
|---|---|
| BREAKING key rename breaks live site if config.json not updated | Update `config.json` as part of this change; the file is in version control |
| Write permission not set on `config/` | Show error flash on `file_put_contents` returning false; document `chmod 664 config/config.json` in README |
| Empty themes dir → dropdown empty → saved slug is invalid | `config_general.php` warns if dir is empty; public view falls back to `classic` |
| Concurrent POSTs overwrite each other silently | Acceptable for a single-admin school site; `LOCK_EX` prevents file corruption |

## Migration Plan

1. Update `config/config.json`: rename `school` → `general`, add `"public": {"theme": "classic"}`.
2. Update all `cfg('school.*')` call sites → `cfg('general.*')` (4 files, 12 occurrences).
3. Rename Authorization sidebar link "Settings" → "Configurations" in `_layout.php`.
4. Add Settings accordion (sa-only) to `_layout.php`.
5. Create `public/css/themes/classic.css` (minimal starter — can be empty or copy of current public styles).
6. Create `admin/config_general.php`.
7. Update `openspec/specs/static-config/spec.md` and `openspec/specs/admin-shell/spec.md`.

Rollback: restore `config/config.json.bak`; revert the 4 PHP files.

## Open Questions

_None — all decisions resolved during grilling session._
