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
# ⚠⚠ THE ACTUAL ROOT CAUSE — "Return to Menu" SLIDING into the minimise control is NOT a spacing
# problem, which is why two rounds of clearance did not fix it and one of them shipped to prod still
# broken. #game-over-overlay is a NAMED grid, and a direct child with no grid-area gets AUTO-PLACED
# into an IMPLICIT cell — top-right, where the control lives. It looks like a "slide" because implicit
# placement is RECOMPUTED as async code adds children after first paint:
# MatchReplayClient.addGameOverButton falls back to `target = overlay` whenever #game-over-stats is
# absent (hotseat has no stats pane), inserting a bare child at the front.
# The fix is a catch-all rule forcing every unplaced direct child into the buttons area.
# ⚠ REPRO + MUTATION (this is the check that matters — do it in the NARROW layout, 700px):
#   build the overlay with a BARE-CHILD button and no stats pane, note the button's y, then insert
#   another bare child and re-measure. Without the catch-all the button jumps (measured 184 -> 248);
#   with it, it does not move (184 -> 184). Both engines.
#
# ⚠ THE COLLISION CASE — the control must never land on "Return to Menu". The fix is deliberately
# CONTAINER-INDEPENDENT and it took two goes to get there:
#   • v1 put the control in a grid area and cleared #game-over-buttons with a top margin. That works
#     only if the buttons live in that container — HOTSEAT's panel does not use it, so it shipped and
#     was still broken.
#   • v2 (current): the control is position:ABSOLUTE at the panel's top-right, and the OVERLAY itself
#     reserves the strip (padding-top 46px expanded, padding-right 44px minimised). Padding on the
#     overlay clears every child of every container at once, whatever an end-game path builds.
#   ⚠ So test it by CONTAINER SHAPE, not just by viewport. Regression matrix, both engines, all clear:
#     button as a BARE CHILD of the overlay · inside #game-over-buttons · inside some other wrapper
#     — each at wide (1700) and narrow (700, the single-column portrait layout).
#   ⚠ And build the overlay WITHOUT a #game-over-title: a titleless panel is what first exposed this.
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
#   minimised  151x33  at (16,1051), stats hidden, title still visible, button "□"
#   restored  1190x880 at (170,110) — identical to expanded, zero page errors.
# WebKit NOT covered: it does not launch on this machine.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true

## WHEN

## EXPECT
