## REMOVED Requirements

### Requirement: SSO callback placeholder
**Reason**: The real OIDC callback is implemented in `admin/auth/callback.php`. This placeholder requirement described a stub that displayed "SSO callback not yet implemented" — that stub no longer exists and the scenarios no longer reflect the system's behaviour.
**Migration**: Refer to the `oidc-callback-user-error` spec for the authoritative requirements governing OIDC callback error handling. The callback itself (token exchange, claim validation, session establishment) is covered by implementation; future spec work should document those behaviours under a dedicated `oidc-callback` capability if full coverage is required.
