# ⚠⚠ NOT A REGRESSION SECTION — a VISUAL fixture, built by DevTools/ui-harness/swusim-opponent-unit-action-xbrowser.mjs.
#
# VISUAL CHECK — an opponent's TS26_15 C-3P0 can be used from the Home panel tile and from the zoomed board
#
#   Discord 2026-10-22: "the TS26 C-3PO unit is not letting them use its ability". "Action [Exhaust]: Deal damage equal to this
#   unit's power to another ground unit. Only opponents may use this ability." — so he is ALWAYS on someone else's board. The
#   offer list had him, but nothing on screen did: the Attack/Ability click was wired to MY arenas only, the glow looked
#   for a "p4…" data-mzid the board never draws, the Home tiles had no unit-action path at all, and CustomInput dropped
#   the opponent-board id even when it arrived (schema: Tests/Cases/ts26/C3p0_DieJediDogs.md LiveInput_*).
#
#   Board: 4 seats, P1 to act. Seat 3 controls C-3PO (a FAR seat: not the Home view's first opponent), P1 has a unit to shoot.
#
## HOW TO RUN
#   cd DevTools/ui-harness && node swusim-opponent-unit-action-xbrowser.mjs
#
## WHAT TO LOOK AT (by eye: open the game as P1)
#   1. Home view (desktop): seat 3's C-3PO tile glows cyan. Click it → a menu with ONLY "Ability" (he is not yours to attack with).
#   2. Ability → C-3PO exhausts and the "Deal 2 damage to another ground unit" target prompt opens.
#   3. Zoom in on seat 3 (desktop or phone): C-3PO glows on their board; tap it → the same Ability-only menu.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_046:1:0]
WithP2GroundArena: [SOR_095:1:0]
WithP3GroundArena: [TS26_15:1:0]
WithP3Base: SOR_026:0
WithP4Base: SOR_026:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:TS26_15
