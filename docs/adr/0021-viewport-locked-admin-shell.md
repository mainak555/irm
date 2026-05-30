# ADR-0021: Viewport-locked admin shell — fixed navbar and sidebar

**Date**: 2026-05-30
**Status**: accepted
**Deciders**: project team

## Context

The admin panel initially used normal document flow. The `<nav>` and `.admin-sidebar`
scrolled with page content. On pages with long tables (user list, carousel management)
the navbar and sidebar disappeared off-screen, forcing users to scroll back to the top
to navigate or change pages — a significant UX friction for a tool-heavy admin interface.

The standard pattern for web admin dashboards is a **viewport-locked chrome**: navbar
and sidebar permanently visible, only the content area scrolls.

## Decision

The admin shell is restructured as a fully viewport-locked layout using CSS `position: fixed`:

| Element | Rule | Effect |
|---|---|---|
| `.navbar` | `position: fixed; top: 0; left: 0; right: 0; z-index: 1030` | Pins navbar across the full viewport top |
| `.admin-wrapper` | `position: fixed; top: 56px; left: 0; right: 0; bottom: 0` | Fills the viewport below the navbar exactly |
| `.admin-sidebar` | `height: 100%; overflow-y: auto` | Sidebar fills the slab; scrolls its own content if nav links overflow |
| `.admin-main` | `overflow-y: auto; height: 100%` | **Only this region scrolls** — all page content must live here |

`56px` is the fixed navbar height, consistently referenced in both PHP (`style="height:56px"`)
and CSS (sidebar-peek top offset, toggle button top offset, and now `.admin-wrapper` top).

The existing sidebar-peek overlay (`position: fixed; top: 56px; height: calc(100vh - 56px)`)
and the toggle button (already `position: fixed`) are unaffected — both already used fixed
positioning anchored to the same 56px offset.

## Alternatives Considered

### Alternative: `position: sticky` navbar and sidebar
Use `position: sticky; top: 0` on the navbar and sidebar.
- **Pros**: Less invasive; no change to document flow.
- **Cons**: Sticky on a flex child requires the parent to have height and overflow constraints
  that are essentially equivalent to the fixed approach but less explicit. In a flex layout
  where `.admin-wrapper` grows with content, sticky sidebar would still scroll away once
  the sidebar height exceeds the viewport.
- **Why not**: Viewport-constrained fixed layout is the correct model for an app-shell
  pattern. Sticky is a better fit for content-page headers, not persistent navigation chrome.

### Alternative: `overflow: hidden` on `body` only
Set `overflow: hidden` on `body` without fixing the navbar, relying on `.admin-main`
`overflow-y: auto` as the only scroll surface.
- **Pros**: Navbar and sidebar stay in flow, avoiding z-index concerns.
- **Cons**: Without `position: fixed` on the navbar, it still occupies flow and
  `.admin-wrapper` must start below it — requiring the same 56px coordination anyway.
  Adds implicit coupling without making the constraint explicit.
- **Why not**: Making `position: fixed` explicit is clearer and matches industry convention.

## Consequences

### Positive
- Navbar and sidebar are always accessible regardless of content scroll depth.
- Matches the UX pattern of industry admin dashboards (VS Code, GitHub, Tailwind UI).
- All scroll state lives in one element (`.admin-main`) — predictable for JS that reads
  `scrollTop` or adds scroll listeners.

### Negative
- Browser "scroll to top on back navigation" restores scroll position on the document,
  not on `.admin-main`. Admin pages should not depend on browser scroll restoration.
- `position: sticky` within page content must be scoped to `.admin-main` as the scroll
  root, not to the viewport. Any future sticky table headers or sticky section headings
  must use `position: sticky` relative to `.admin-main`'s scroll container.

### Risks
- If a future page embeds a full-height iframe or a component that needs its own vertical
  scroll, the 100%-height constraint on `.admin-main` must be respected. Mitigation:
  ensure such components set `height: 100%; overflow-y: auto` on their own container.
