# VISUAL CHECK — Team Suns: the carousel must not call your teammate an opponent
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load by hand in the Test Schema
# Editor (zzTestSchemaEditor.php) as P1.
#
# swuBuildViews() labels every non-self seat 'vs P' + n. In Team Suns that made a player's own ALLY
# read "vs P3". This is the ONLY in-game change Phase 4 makes.
#
#   • Carousel reads:  Home | vs P2 | P3 | vs P4
#     - your ALLY at seat 3 reads "P3" — NO "vs"
#     - the two enemies still read "vs P2" / "vs P4"
#   • FOUR entries — the same count as Twin Suns. The teammate is NOT filtered out of the view list.
#   • Home view still previews ALL THREE boards, ally included.
#   • Zoom In on P3's tile still opens that matchup and the board renders normally.
#
# ⚠ DO NOT "FIX" THE LABEL BY FILTERING THE TEAMMATE OUT of `opps` in swuBuildViews. That drops their
#   preview tile AND their carousel entry, breaking Zoom In on your own ally — the opposite of USER
#   RULING 2026-08-25 (the home view stays as it is; Zoom In is how you look at your ally's board).
#
# ⚠ TO A/B AGAINST TWIN SUNS: delete the WithP1GlobalEffect line below and reload. Same board, same
#   tiles, same geometry — the ONLY difference must be that P3 goes back to reading "vs P3".
#
# Console cross-check: window.swuViews.map(v => v.label)  and  window.SWUIsTeamGame

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
OPPONENTSOF:1:2,4
P1GROUNDARENACOUNT:2
P3GROUNDARENACOUNT:2
