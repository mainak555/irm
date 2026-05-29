## 1. Restyle error.php

- [x] 1.1 Replace `<div class="text-center">` with `.auth-card w-100 px-3` outer wrapper + `.card shadow-sm` + `.card-body p-4` inner structure, matching the layout of `admin/login.php`
- [x] 1.2 Add `<h4 class="card-title mb-1">Access Not Granted</h4>` as the first element inside `.card-body`
- [x] 1.3 Wrap the dynamic `h($msg)` output in `<p class="text-muted small mb-3">` below the card title (remove the bare `<h2>`)
- [x] 1.4 Keep "Please contact an administrator to request access." as a `<p class="text-muted mb-4">` below the dynamic message
- [x] 1.5 Add `w-100` to the "Back to Login" `<a>` button so it spans the full card width

## 2. Verification

- [x] 2.1 Load the error page via OIDC flow with an unregistered email — confirm card layout renders with the provisioning message in the body
- [x] 2.2 Navigate directly to `/admin/auth/error.php` with no session key — confirm generic fallback message renders inside the same card layout
- [x] 2.3 Check light mode and dark mode — card border, shadow, and background should match the login page appearance in both themes
