## 1. Server guard (`admin/users_ajax.php`)

- [x] 1.1 Add `bool $block_same_rank = true` parameter to `guard_target()` and, when `true`, reject with HTTP 403 if `role_rank($target['role']) === role_rank($me['role'])`
- [x] 1.2 Update the `toggle_active` case to call `guard_target($id, block_same_rank: false)` so same-rank peers can be toggled active/inactive

## 2. UI row rendering (`admin/users.php`)

- [x] 2.1 Add `$is_peer` variable: true when target rank equals actor rank and row is neither sentinel, self, nor higher-rank; add `irm-peer-row` branch to `$tr_class` computation
- [x] 2.2 Make the role dropdown and SSO checkbox disabled when `$locked || $is_peer` (instead of `$locked` only)
- [x] 2.3 Make the action menu (Edit Name, Reset Password, Delete) absent when `$locked || $is_peer`
- [x] 2.4 Verify the active toggle uses only `$locked` as its disabled condition (not `$is_peer`), so it stays live on peer rows

## 3. CSS (`admin/style.css`)

- [x] 3.1 Add `.irm-peer-row` rule: same muted background as `.irm-sa-row`, but reduced opacity scoped to non-toggle controls (e.g. `.irm-peer-row td:not(.col-active)`) so the active toggle retains full visual weight

## 4. ADR (`docs/adr/`)

- [x] 4.1 Create `docs/adr/0014-guard-target-same-rank-param.md` documenting the parameterised-guard decision (alternatives: duplicate function, inline check per case); update `docs/adr/README.md` index
