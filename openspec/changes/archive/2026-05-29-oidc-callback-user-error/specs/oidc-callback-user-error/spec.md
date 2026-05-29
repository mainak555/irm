## ADDED Requirements

### Requirement: OIDC error page uses auth-card layout
`/admin/auth/error.php` SHALL render its content inside a card-framed panel matching the visual layout of `/admin/login.php` — an `.auth-card` wrapper containing a `.card.shadow-sm` element with a `.card-body.p-4`. The card title SHALL always be the fixed string "Access Not Granted". The dynamic session message (`$_SESSION['oidc_provision_error']`) SHALL appear as body text below the title, followed by the fixed sub-message "Please contact an administrator to request access." The "Back to Login" link SHALL be rendered as a full-width `btn-primary` button inside the card body.

#### Scenario: Error page presents card-framed layout matching the login page
- **WHEN** any visitor loads `/admin/auth/error.php`
- **THEN** the page renders a card-framed panel using the same `.auth-card` wrapper and `.card` structure as `/admin/login.php`
- **THEN** the panel is centred on the page with a max-width consistent with the login card

#### Scenario: Card title is always "Access Not Granted"
- **WHEN** any visitor loads `/admin/auth/error.php`
- **THEN** the card heading reads "Access Not Granted" regardless of the content of the session error message

#### Scenario: "Back to Login" button spans the full card width
- **WHEN** any visitor loads `/admin/auth/error.php`
- **THEN** the "Back to Login" link is rendered as a full-width button inside the card body
