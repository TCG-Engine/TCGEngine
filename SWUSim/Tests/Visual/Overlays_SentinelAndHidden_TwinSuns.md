# VISUAL CHECK — Sentinel / Hidden overlays on the Twin Suns opponent PREVIEW tiles match the board
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint). Load it in the Test Schema Editor and Run all,
# or run the automated pass: node DevTools/ui-harness/swusim-overlay-brightness-xbrowser.mjs
#
# WHAT CHANGED (2026-10-23): see Overlays_SentinelAndHidden_1v1.md. The preview tiles do NOT use the schema's overlay renderer:
# they draw their own background-image spans (.swu-mb-ov-sentinel / .swu-mb-ov-hidden, GameLayoutShared.php), with their OWN
# copy of the Sentinel opacity. So the same two changes were made there:
#   1. .swu-mb-ov-sentinel opacity 0.88 (was 0.71) — must equal the board's.
#   2. .swu-mb-ov (both overlays) filter: brightness(1.2).
#
# What to look at (as P1, after Run all): the three opponent preview tiles across the top.
#   a. P2's tile: Cell Block Guard (Sentinel) — the wall as solid as on the full board; and Teebo, played THIS phase (Hidden) —
#      lighter smoke.
#   b. P3's tile: Cloud City Wing Guard in space — the Sentinel wall.
#   c. P4's tile: an EXHAUSTED Cell Block Guard — wall present; the exhausted dimming unchanged.
#   d. "Zoom in" on P2: the full-size board shows the same overlays at the same strength (compare a. side by side).
#
# BOARD SHAPE: four seats, two leaders each (a Twin Suns seat always has two — see memory twinsuns-visual-tests-two-leaders);
# P2 is active so it can play Teebo (Hidden shows only on a unit played this phase).

## GIVEN
CommonSetup: rrk/rrw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myResources:6; theirResources:4}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP1GroundArena: SOR_229:1:0
WithP2GroundArena: SOR_229:1:0
WithP2Hand: HMW_162
WithP3SpaceArena: SOR_063:1:0
WithP4GroundArena: SOR_229:0:0

## WHEN
- P2>PlayHand:0

## EXPECT
