# VISUAL CHECK — the Team Suns board IS the Twin Suns board
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load by hand in the Test Schema
# Editor (zzTestSchemaEditor.php) as P1.
#
# USER RULING 2026-08-25: "keep the home view looking the same as in Twin Suns — it's already been well
# received." Phase 4 adds NO ally view, NO ally tile treatment, and does not resurrect the removed order
# strip. This fixture is how that stays true.
#
# HOW TO A/B: load it once as written (Team Suns), screenshot; then DELETE the WithP1GlobalEffect line,
# reload, screenshot again (plain 4-player Twin Suns). Compare the two.
#
# WHAT TO LOOK AT
#   • THE INVARIANT — for each .swu-home-strip, the first .swu-mb-leader's offset inside its own tile
#     must be EQUAL on all three tiles, and the SAME in both formats. Measured 29,10 at 1700x1100,
#     matching the 29px BaseStates_AllFour_TwinSuns.md documents.
#         dx = leaderRect.left - tileRect.left ;  dy = leaderRect.top - tileRect.top
#   • ⚠ DO NOT ASSERT RAW TILE WIDTH EQUALITY. Measured 390/392/390 in one game and 390/390/390 in
#     another — tile width is CONTENT-driven (a seat whose leader art is wider makes its tile a pixel or
#     two wider), in BOTH formats. An earlier version of this check asserted exact width equality and
#     produced a false alarm that looked like a Team Suns regression. The leader OFFSET is the invariant.
#   • NO ally decoration: no .swu-home-strip carries a team class, badge, border or label in either
#     format. Three tiles in both — the ally is previewed exactly like an opponent.
#   • NO order strip: #swuOrderStrip must not exist. It was removed on purpose
#     (GameLayoutShared.php:1967) because it duplicated the turn-player signal the tiles already carry.
#     If it reappears, someone has designed against the stale §8.2 of the spec.
#
# VERIFIED 2026-08-25 — chromium and firefox: leader offset 29,10 on all three tiles in BOTH formats,
# no ally class, identical. webkit NOT RUN (hangs at launch on this machine). TWO-engine coverage.

## GIVEN
#// Team Suns seating: seats 1,3 = RED (you + your ally at P3); seats 2,4 = BLUE (the enemy team).
#// Twin Suns decks run TWO leaders that must share a force-side (all Villainy here).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
#// ⚠ THIS LINE IS WHAT MAKES IT TEAM SUNS. Remove it and the same fixture is a plain 4-player Twin
#// Suns game — which is exactly how to A/B the two boards (see WHAT TO LOOK AT).
WithP1GlobalEffect: SWU_MODE_TEAMS
WithP1Resources: 3
WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP1SpaceArena:  [SOR_031:1:0 SOR_040:1:3]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]
WithP2SpaceArena:  [SOR_050:1:0 SOR_052:1:3]
WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2]
WithP3SpaceArena:  [SOR_060:1:0 SOR_066:1:3]
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4GroundArena: [SOR_038:1:0 SOR_039:1:2]
WithP4SpaceArena:  [SOR_086:1:0 SOR_089:1:3]
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
SEATCOUNT:4
P1SPACEARENACOUNT:2
P2SPACEARENACOUNT:2
P3SPACEARENACOUNT:2
P4SPACEARENACOUNT:2
