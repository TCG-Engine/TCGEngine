# VISUAL CHECK — the end-game results panel can be minimised and restored
#
# There is no GIVEN that ends a game, so drive it the way a real game does: finish a match (or, for a
# quick look, set the overlay up by hand in the console):
#   var o=document.getElementById('game-over-overlay'); o.classList.add('active','won');
#
# WHAT TO LOOK AT
#   • A control sits at the TOP-RIGHT of the panel: "–" minimise. Click it and the panel collapses to
#     a small title bar in the BOTTOM-LEFT showing just the result ("YOU WON!") and a "□" restore
#     button. Click that and it comes back exactly as it was.
#   • Minimised, the board underneath must be fully visible and usable — that is the whole point. The
#     panel is only 80% of the screen precisely so the board stays reachable, but before this there was
#     no way to see the part it covered.
#   • The result text stays readable while minimised (it is the one thing worth keeping); the stats
#     pane, the buttons and the winners subtitle all hide.
#
# ⚠ THE COLLISION CASE, and it is the non-obvious one: the minimise control and the first button
# ("Return to Menu") only overlap when the TITLE ROW has no height to hold the control — a results
# panel with no title (the plain non-match overlay) collapses that row to 0, so the 26px button
# overflows down into the buttons row and lands on the button. #game-over-buttons therefore carries a
# 30px top margin. Test it WITHOUT a #game-over-title element, and in BOTH layouts: the wide 2-column
# one AND the portrait/max-width:760px single-column one, where the buttons span full width and reach
# the panel's right edge. Measured with no title: wide toggle y 127-153 vs button y 195-221; narrow
# toggle y 117-143 vs button y 183-209 — no overlap in either, both engines.
#
# ⚠ #game-over-overlay is a NAMED CSS GRID. Minimising is NOT just hiding children: an unplaced child
# in a grid with named areas gets AUTO-PLACED into an implicit cell, so the collapsed state declares
# its own grid-template-areas ("title toggle"), exactly as .has-subtitle does for the winners line.
# Hiding is done with a child selector that EXCLUDES the title and the button, rather than a list of
# ids — otherwise a child added to the shared markup later would reappear in the collapsed bar.
#
# ⚠ The toggle is INJECTED by SWUSim, because #game-over-overlay is shared Core markup while this
# collapse behaviour is SWUSim's panel-not-takeover treatment. It is driven by a light 700ms watcher,
# NOT by pollGlobals: pollGlobals runs on data CHANGE, so an overlay appearing while the board is idle
# would never get its control, and the replay client tears the overlay down and rebuilds it, which a
# one-shot hook would miss. If the button ever goes missing, check that watcher first.
#
# VERIFIED 2026-08-20, Chromium + Firefox at 1700x1100:
#   expanded  1190x880 at (170,110), stats visible, button "–"
#   minimised  155x44  at (16,1040), stats hidden, title still visible, button "□"
#   restored  1190x880 at (170,110) — identical to expanded, zero page errors.
# WebKit NOT covered: it does not launch on this machine.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true

## WHEN

## EXPECT
