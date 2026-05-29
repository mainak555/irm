# ADR-0003: Explicit role passing in require_auth() — no implicit SA superrole

**Date**: 2026-05-29
**Status**: accepted
**Deciders**: project owner

## Context

The role hierarchy is `sa > admin > faculty > user`. SA users must be able to access everything `admin` users can access, plus SA-only pages. `require_auth()` uses a strict `in_array($role, $allowedRoles)` check. Without a convention, pages accessible to both `sa` and `admin` either need two role values passed, or `require_auth()` must be modified to treat `sa` as a superrole that bypasses all checks.

## Decision

We always pass all allowed roles explicitly. Pages shared between SA and admin use `require_auth('sa', 'admin')`. SA-only pages use `require_auth('sa')`. The `require_auth()` function remains a pure, transparent guard with no hidden hierarchy.

## Alternatives Considered

### Alternative 1: Implicit SA superrole inside require_auth()
- **Pros**: Call sites are simpler — `require_auth('admin')` automatically admits SA too; no need to remember to pass both
- **Cons**: A security-critical function has a hidden privilege escalation rule; adding a new role or adjusting the hierarchy requires editing `require_auth()` and re-auditing all call sites
- **Why not**: Transparency and auditability. A developer reading `require_auth('admin')` on a page should be able to understand who can access it without reading the guard's implementation.

## Consequences

### Positive
- Every call to `require_auth()` is self-documenting — the allowed roles are visible at the call site
- The function remains testable in isolation with no hidden state
- Adding future roles (e.g., `moderator`) does not require changing `require_auth()` internals

### Negative
- Call sites are slightly more verbose for shared pages: `require_auth('sa', 'admin')` vs `require_auth('admin')`
- If the role hierarchy changes (e.g., a new role is inserted between `sa` and `admin`), all relevant call sites need updating

### Risks
- A developer could accidentally call `require_auth('admin')` on a page that SA also needs, inadvertently blocking SA — mitigated by code review and the explicit convention documented here
