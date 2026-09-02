# VISUAL CHECK — MOBILE home rows are CLICKABLE: the Zoom button and the Discard chip
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# Load it by hand in the Test Schema Editor, then append &swuLayout=mobile to the board URL.
#
# WHY THIS FILE EXISTS — a bug that shipped and stayed invisible. The mobile Zoom button did nothing,
# from the day the mobile seat rows were introduced, for TWO independent reasons stacked on each other.
# Either one alone is enough to kill it, so a fix that finds only one still looks broken:
#
#   1. NO CLICK COULD REACH IT. The shared .swu-home-strips container is `pointer-events: none`, so the
#      fixed band does not swallow clicks meant for the board beneath it. DESKTOP re-enables them on
#      .swu-home-strip. The mobile row is a DIFFERENT element and never got the same treatment, so
#      everything inside it was click-dead — including the Discard chip, which nobody had reported.
#   2. NOTHING WAS LISTENING. The click delegate matched only `.swu-mb-zoom` (desktop) and then looked
#      for a `.swu-home-strip` ancestor to read `data-view` off. Mobile renders `.swu-sr-zoom` inside
#      `.swu-seat-row`, which matched neither half — and the row carried no `data-view` at all.
#
# ⚠ NEITHER FAILURE IS VISIBLE IN A SCREENSHOT. The row renders perfectly in both broken states; the
#   only symptom is that tapping does nothing. So this check is a SEQUENCE, not a look — and it is the
#   reason a "does it render?" pass over the mobile rows is not evidence that they work.
#
# What to do, in order:
#   1. On the home view, tap the magnifier on the P3 row. The view must switch to the you-vs-P3
#      matchup — P3's board, not P2's. Tapping P2's magnifier must open P2's, and P4's must open P4's;
#      check at least two, because a row that hands over a hardcoded or off-by-one view index opens
#      the WRONG opponent and still looks like the button "works".
#   2. Use the back/pair nav to return to the home view.
#   3. Tap the DISCARD chip on the P2 row. That seat's discard pile must open. (It is safe for any
#      seat: the discard is a PUBLIC zone. The Hand chip beside it must NOT be tappable — the hand is
#      hidden.)
#   4. Tap the row's background, away from any control — it must do nothing at all, so re-enabling
#      pointer events has not turned the whole row into one big button.
#
# Seats hold different discard piles so step 3 proves which seat's pile opened:
#   P2 discard 3 · P3 discard 1 · P4 discard 5

## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP2Hand: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046 SOR_143]
WithP2Discard: [SOR_095 SOR_046 SOR_143]
WithP2GroundArena: SOR_157:1:0
WithP3Discard: [SOR_051]
WithP3Deck: [SOR_095]
WithP3GroundArena: SOR_095:1:0
WithP4Discard: [SOR_095 SOR_046 SOR_143 SOR_157 SOR_051]
WithP4Deck: [SOR_095 SOR_046]
WithP4SpaceArena: SOR_141:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P2DISCARDCOUNT:3
P3DISCARDCOUNT:1
P4DISCARDCOUNT:5
