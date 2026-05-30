# ADR-0023: Page Designer — External Tab Preview Replaces Inline iframe

**Date**: 2026-05-31
**Status**: accepted
**Deciders**: Mainak Chowdhury

## Context

The original page designer (`admin/page_designer.php`) used a two-column layout:
the slot editor on the left (col-lg-7) and a live `<iframe>` preview pane on the
right (col-lg-5). The preview worked by POSTing the current draft layout JSON to
`admin/page_preview.php` after a 500 ms debounce, then injecting the returned HTML
into the iframe's `srcdoc` attribute.

Problems with the inline preview:

1. **Narrow canvas.** The right column was 5/12 of the viewport — too small to judge
   actual page layout, especially on portrait or 1080p screens.
2. **Unsaved-state illusion.** The iframe rendered the unsaved draft, not the live
   page. Admins sometimes mistook the preview for the real site.
3. **Dead fetch overhead.** Every keystroke scheduled a debounced network round-trip
   to `page_preview.php`, even for minor edits.
4. **Form width penalty.** The slot editor was squeezed to 7/12 to make room for the
   preview, reducing the usable area for textarea content editing.
5. **JS complexity.** The feature required `collectLayout`, `schedulePreview`,
   `fetchPreview`, a debounce timer, and form-level `input`/`change` listeners —
   all dead weight once the page is saved.

## Decision

Remove the inline preview pane entirely. Add a "Preview" button in the page header,
placed to the right of the "← Pages" back button. The button is rendered only in
edit mode (not create mode — the page has no public URL until first save). Clicking
it opens `/<slug>` in a new browser tab, showing the actual saved page at full width
in the public theme.

The slot editor form is promoted to full width (`col-12`).

## Alternatives Considered

### Keep the inline iframe
- **Pros**: Live unsaved-draft feedback without navigating away.
- **Cons**: See context problems above. The slot-based editor changes the page structure
  infrequently — the feedback loop that matters most is "does the saved page look right?"
  not "does the unsaved HTML string look right?"
- **Why not**: Cost (complexity, width penalty) outweighs the benefit.

### Modal overlay preview
- **Pros**: Full-width rendering while staying on the designer.
- **Cons**: Modal + iframe sandboxing adds non-trivial JS; still shows unsaved state.
- **Why not**: Overkill for an infrequent admin workflow.

### Keep inline preview but increase its column width
- **Pros**: Better canvas.
- **Cons**: Squeezes the editor further; doesn't address unsaved-state confusion or fetch overhead.
- **Why not**: Doesn't solve the root issues.

## Consequences

### Positive
- Slot editor is full-width — more room for HTML textareas and slot configuration.
- Preview always shows the real saved page in the real public theme at real viewport width.
- All preview-related JS removed (`collectLayout`, `schedulePreview`, `fetchPreview`,
  debounce timer, form event listeners).
- `admin/page_preview.php` is still present and usable for future tooling; it is simply
  no longer wired to the designer UI.

### Negative
- No unsaved-draft preview. Admin must save before previewing.
- Clicking "Preview" before the first save in create mode is not possible (button hidden).

### Risks
- None identified. The public `/<slug>` route is already secured by the public router;
  opening it in a new tab is read-only.
