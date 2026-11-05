# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the Limited DRAFT pick screen (SWUSim/LimitedDraft.php), not a gamestate.
#
# VISUAL CHECK — the SWUSim Limited Draft pick screen, in the "New Petranaki HUD" style
#
#   What changed: a new standalone page where a drafter picks from the pack in front of them
#   (docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.2; mockup
#   docs/superpowers/specs/2026-11-04-limited-mockups/draft-pick.html — owner: "lgtm. actually perfect").
#   Markup: SWUSim/LimitedDraft.php; client: SWUSim/Limited/ui/limited-draft.js; data: SWUSim/LimitedDraftAction.php.
#   Styles: SharedUI/Sites/SWUSim/css/swusim-menu-2.css "Limited draft — the pick screen", scoped `body:has(.ld-root)`.
#
#   The bugs it guards: a misclick costing a pick (a click only SELECTS; "Pick <title>" confirms); a double click picking
#   twice (each pick names its pack); a hidden "all picks" list showing anyway (`.ld-all[hidden]` — `display:grid` beats
#   the hidden attribute); "You(1)" with no space (the pill joins name and count with a no-break space).

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px):
    cd DevTools/ui-harness && node swusim-limited-draft-xbrowser.mjs [BASE] [SHOTS_DIR]
Fixture: SWUSim/DevTools/tests/limited_fixture.php?mode=draft&players=3&phase=drafting (the leader draft just started).
Whole drafts end to end, 3 and 8 claudebots: node swusim-limited-draft-sim.mjs (SWUSim/Tests/Visual/LimitedDraftSim.md).

## WHAT TO LOOK AT (in order; each one can fail)

1. **Top bar.** "LEADER DRAFT · PICK 1 OF 3" (then "PACK 2 (SHD) · PICK 5 OF 14"), "PASSING ◀ RIGHT" / "PASSING LEFT ▶",
   and a gold m:ss clock at the right that counts down in place and turns red under a minute.
2. **Status line.** "Waiting on you · 1 more pack queued behind this one", or "Waiting for <names>…" with nothing in front.
3. **Pod row.** One pill per seat in seating order with its queued count "(n)"; yours has a gold rim and reads "You";
   a seat with 2+ packs waiting is red with ⏳; a dropped seat is struck through.
3b. **Leaders drafted** (owner 2026-11-05 — public info). Under the pod row: one entry per seat in seating order ("You"
   in gold), each with that seat's drafted leaders as small landscape art thumbnails, or "—" before its first pick; a
   dropped seat is faded and struck through. Hover (or long-press) a thumbnail for the full-size card. At 390px it is
   a "LEADERS DRAFTED" bar that unfolds on tap. Card picks are never shown for anyone but you.
4. **Pack.** Square card art in the builder's aspect order, a rarity badge (C/U/R/L/S) bottom-left. A click gives a gold
   outline and the gold "PICK <title>" button lights; nothing is picked until it is pressed.
5. **Preview.** Hover 500 ms → a 300px full card beside the cursor; leaving or clicking hides it.
6. **Picks panel** (right on desktop): "YOUR PICKS (n)", the leaders by title, six aspect bars in the aspect colours
   (Vig blue, Com green, Agg red, Cun yellow, Her ivory, Vil dark) labelled under, "Leaning: X / Y — a timeout auto-pick
   follows this lean.", "SHOW ALL PICKS" which opens the list by aspect, "Only you can see your picks."
7. **Phone (390px).** The panel is a "YOUR PICKS (n)" bar under the pack that opens on tap; the chat is a block at the
   foot of the page (no floating launcher); no sideways scroll; every button ≥44px.
8. **Leaving.** When the draft ends the page goes to the deckbuilder by itself; if the event ends, back to the room.

## NEIGHBOURING INVARIANTS (must not change)

- The builder (LimitedBuilder.php) and the room panel are untouched by the ld- rules (scoped `body:has(.ld-root)`).

## CROSS-BROWSER / MOBILE

Chromium, Firefox and WebKit at 1440px and 390px. Responsive CSS, no separate mobile markup. ⚠ The long-press preview
and native touch on a real iPhone (Safari) are the owner's manual checks.
