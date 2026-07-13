# VISUAL CHECK — Twin Suns 4-player, every seat has 2 units in EACH arena
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load by hand in the
# Test Schema Editor (zzTestSchemaEditor.php) as P1 to eyeball the Twin Suns UI wrapper:
#   • Order strip (top): P1 (you) active + P2/P3/P4 waiting.
#   • Pair-switcher (bottom arrows/dots): 2 FIXED-PAIR views —
#       View 1 "Your pair"  = you (P1) vs P2.
#       View 2 "Other pair" = P3 vs P4 (neither is you; P3 renders at the bottom, P4 at the top).
#     (No home split-top in 4-player — that's a 3-player-only view.)
#   • Concat leaders: P1 & P2 each show TWO leaders side-by-side over the base.
#   • Each seat has 2 ground + 2 space units (one damaged in each arena for the damage counter).
#
# ⚠ Harness limitation: only seats 1–2 can have leaders (CommonSetup seeds my/their). Seats 3 & 4
#   have units + base but no leader — the "Other pair" view shows their leader zones empty. The
#   engine supports leaders there; the TEST harness just can't seed seats 3/4 leaders.
# ⚠ Attacking the OTHER pair (P3/P4) from View 1 needs cross-view targeting, which is deferred
#   (engine target-tagging). This iteration ships 4-player as the navigation + table-reading shell.
#
# Distinct NON-LEADER units per seat (real Unit cards, not leaders) so the leader zone clearly shows
# the two undeployed leaders while each arena shows two ordinary units:
#   P1 ground SOR_032 / SOR_033(2dmg)   space SOR_031 / SOR_040(3dmg)
#   P2 ground SOR_034 / SOR_035(2dmg)   space SOR_050 / SOR_052(3dmg)
#   P3 ground SOR_036 / SOR_037(2dmg)   space SOR_060 / SOR_066(3dmg)   base SOR_026 (5 dmg)
#   P4 ground SOR_038 / SOR_039(2dmg)   space SOR_086 / SOR_089(3dmg)   base SOR_026 (8 dmg)
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:IBH_053; theirLeader:IBH_053; theirLeader2:IBH_053}
WithSeatOrder: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1Resources: 3

WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP1SpaceArena:  [SOR_031:1:0 SOR_040:1:3]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]
WithP2SpaceArena:  [SOR_050:1:0 SOR_052:1:3]
WithP3GroundArena: [SOR_036:1:0 SOR_037:1:2]
WithP3SpaceArena:  [SOR_060:1:0 SOR_066:1:3]
WithP3Base: SOR_026:5
WithP4GroundArena: [SOR_038:1:0 SOR_039:1:2]
WithP4SpaceArena:  [SOR_086:1:0 SOR_089:1:3]
WithP4Base: SOR_026:8

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
