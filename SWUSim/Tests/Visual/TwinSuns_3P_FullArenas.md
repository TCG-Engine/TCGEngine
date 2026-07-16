# VISUAL CHECK — Twin Suns 3-player, every seat has 2 units in EACH arena
#
# Visual-only schema (Tests/Visual/ is NOT scanned by the regression). Load by hand in the
# Test Schema Editor (zzTestSchemaEditor.php) as P1 to eyeball the Twin Suns UI wrapper:
#   • Order strip (top): P1 (you) active + P2/P3 waiting.
#   • Two-level nav: HOME (previews) → click a preview → matchup (vs P2 / vs P3) with a "Go back" button.
#   • HOME view: the opponent board region is REPLACED by a mini-board preview per opponent — each a
#     real shrunk board (leaders + base row over Space/Ground unit thumbnails with exhaust + damage),
#     squeezed into the board area left of the chat. Click a preview → that opponent's matchup.
#   • Concat leaders: P1, P2 AND P3 each show TWO leaders side-by-side over the base.
#   • Each seat has 2 ground + 2 space units (one damaged in each arena for the damage counter).
#   • Switching View-as (W) to P2/P3 loads that seat's full board (seats 3/4 are now real players).
#
# Distinct NON-LEADER units per seat (real Unit cards, not leaders) so the leader zone clearly shows
# the two undeployed leaders while each arena shows two ordinary units:
#   P1 ground SOR_032 / SOR_033(2dmg)   space SOR_031 / SOR_040(3dmg)   leaders Vader+Kylo
#   P2 ground SOR_034 / SOR_035(2dmg)   space SOR_050 / SOR_052(3dmg)   leaders Gideon+Bossk
#   P3 ground SOR_036 / SOR_037(2dmg)   space SOR_060 / SOR_066(3dmg)   base SOR_026 (5 dmg)  leaders CadBane+Aphra
#
# No WHEN steps — the initial GIVEN state is the whole check.

## GIVEN
#// Twin Suns decks run TWO leaders that must share a force-side (all Villainy here): P1 = Darth Vader
#// (IBH_053) + Kylo Ren (SHD_011); P2 = Moff Gideon (SHD_007) + Bossk (SHD_010).
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
WithSeatOrder: 123
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
#// P3 also runs two same-side (Villainy) leaders now that the harness seeds seats 3/4: Cad Bane + Aphra.
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015

## WHEN

## EXPECT
SEATCOUNT:3
P1GROUNDARENACOUNT:2
P1SPACEARENACOUNT:2
P3LEADERCOUNT:2
